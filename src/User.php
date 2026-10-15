<?php
if (!defined('APP_INIT')) { http_response_code(403); exit; }

require_once __DIR__ . '/../config/Database.php';

class User{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT user_id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        return (bool) $stmt->fetch();
    }

    public function idNumberExists(string $idNumber): bool
    {
        $stmt = $this->db->prepare('SELECT user_id FROM users WHERE id_number = :id_number');
        $stmt->execute([':id_number' => $idNumber]);
        return (bool) $stmt->fetch();
    }

    public function register(array $data): array
    {
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }
        if ($this->idNumberExists($data['id_number'])) {
            return ['success' => false, 'message' => 'This Student/Employee ID is already registered.'];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO users (last_name, first_name, middle_name, email, password_hash, role, id_number, phone)
             VALUES (:last_name, :first_name, :middle_name, :email, :password_hash, :role, :id_number, :phone)'
        );

        $stmt->execute([
            ':last_name'     => $data['last_name'],
            ':first_name'    => $data['first_name'],
            ':middle_name'   => $data['middle_name'] ?: null,
            ':email'         => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role'          => 'borrower',
            ':id_number'     => $data['id_number'],
            ':phone'         => $data['phone'] ?: null,
        ]);

        return ['success' => true, 'message' => 'Account created. You can now sign in.'];
    }

    public function login(string $identifier, string $password): array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email OR id_number = :id_number');
        $stmt->execute([':email' => $identifier, ':id_number' => $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email/ID or password.'];
        }

        $fullName = trim(preg_replace(
            '/\s+/', ' ',
            ($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '')
        ));

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $fullName;
        $_SESSION['role']      = $user['role'];

        return ['success' => true, 'role' => $user['role']];
    }
    public function listAll(?string $roleFilter = null): array {
        $sql = 'SELECT user_id, last_name, first_name, middle_name, email, role, id_number, phone, created_at FROM users';
        $param =[];
        $allRoles = ['staff', 'admin', 'borrower'];
        
        if ($roleFilter !== null && in_array($roleFilter, $allRoles, true)) {
            $sql .= ' WHERE role = :role ';
            $param[':role'] = $roleFilter;
        }

        $sql .= ' ORDER BY last_name ASC ';
        $stm = $this->db->prepare($sql);
        $stm->execute($param);

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getById(int $userId): ?array {
        $stm = $this->db->prepare(
            "SELECT u.user_id, u.last_name, u.first_name, u.middle_name, u.email, u.role, u.id_number, u.created_at, 
            sp.can_approve_requests, sp.can_inspect_returns, sp.can_confirm_pickup, sp.can_manage_catalog 
            FROM users u 
            LEFT JOIN staff_permissions sp ON u.user_id = sp.user_id 
            WHERE u.user_id = :user_id
        ");

        $stm->execute([':user_id' => $userId]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
    public function updateRole(int $userId, string $newRole): array
    {
        $allRoles = ['staff', 'admin', 'borrower'];

        if (!in_array($newRole, $allRoles, true)) {
            return ['success' => false, 'message' => 'Invalid role specified.'];
        }

        try {
            $this->db->beginTransaction();

            $stm = $this->db->prepare('SELECT role FROM users WHERE user_id = :user_id FOR UPDATE');
            $stm->execute([':user_id' => $userId]);
            $currentRole = $stm->fetchColumn();

            if ($currentRole === false) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'That user does not exist.'];
            }

            // The system must never end up with zero admins (nobody could fix it).
            if ($currentRole === 'admin' && $newRole !== 'admin') {
                $adminCount = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'admin' FOR UPDATE")->fetchColumn();
                if ($adminCount <= 1) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'There must be at least one admin.'];
                }
            }

            $stm = $this->db->prepare('UPDATE users SET role = :role WHERE user_id = :user_id');
            $stm->execute([':role' => $newRole, ':user_id' => $userId]);

            if ($newRole === 'staff') {
                $stm = $this->db->prepare(
                    'INSERT IGNORE INTO staff_permissions
                        (user_id, can_approve_requests, can_inspect_returns, can_confirm_pickup, can_manage_catalog)
                     VALUES (:user_id, 0, 0, 0, 0)'
                );
                $stm->execute([':user_id' => $userId]);
            } elseif ($newRole === 'borrower') {
                $stm = $this->db->prepare(
                    'UPDATE staff_permissions
                     SET can_approve_requests = 0, can_inspect_returns = 0, can_confirm_pickup = 0, can_manage_catalog = 0
                     WHERE user_id = :user_id'
                );
                $stm->execute([':user_id' => $userId]);
            }

            $this->db->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('User::updateRole failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not update the role. Please try again.'];
        }
    }
    public function updateStaffPermissions(int $userId, array $permissions, ?int $grantedBy = null): array
    {
        try {
            // Ensure the user is actually staff or admin first
            $stm = $this->db->prepare('SELECT role FROM users WHERE user_id = :user_id');
            $stm->execute([':user_id' => $userId]);
            $role = $stm->fetchColumn();

            if ($role === false) {
                return ['success' => false, 'message' => 'User not found.'];
            }

            if ($role === 'borrower') {
                return ['success' => false, 'message' => 'Cannot assign staff permissions to a regular borrower. Promote them to staff first.'];
            }

            // Who is *allowed* to call this is enforced by the endpoint
            // (require_role(['admin'])); $role above is the TARGET user's role.

            // UPSERT: Insert or update their granular permissions.
            // Connection uses real prepared statements (EMULATE_PREPARES = false),
            // which do not allow the same :name twice, so the UPDATE half gets
            // its own placeholders.
            $stm = $this->db->prepare(
                'INSERT INTO staff_permissions
                    (user_id, can_approve_requests, can_inspect_returns, can_confirm_pickup, can_manage_catalog, granted_by)
                VALUES
                    (:user_id, :approve, :inspect, :pickup, :catalog, :granted_by)
                ON DUPLICATE KEY UPDATE
                    can_approve_requests = :approve_u,
                    can_inspect_returns  = :inspect_u,
                    can_confirm_pickup   = :pickup_u,
                    can_manage_catalog   = :catalog_u,
                    granted_by           = :granted_by_u'
            );

            $approve = empty($permissions['can_approve_requests']) ? 0 : 1;
            $inspect = empty($permissions['can_inspect_returns'])  ? 0 : 1;
            $pickup  = empty($permissions['can_confirm_pickup'])   ? 0 : 1;
            $catalog = empty($permissions['can_manage_catalog'])   ? 0 : 1;

            $stm->execute([
                ':user_id'      => $userId,
                ':approve'      => $approve,
                ':inspect'      => $inspect,
                ':pickup'       => $pickup,
                ':catalog'      => $catalog,
                ':granted_by'   => $grantedBy,
                ':approve_u'    => $approve,
                ':inspect_u'    => $inspect,
                ':pickup_u'     => $pickup,
                ':catalog_u'    => $catalog,
                ':granted_by_u' => $grantedBy,
            ]);

            return ['success' => true, 'message' => 'Staff permissions updated successfully.'];

        } catch (Throwable $e) {
            error_log('User::updateStaffPermissions failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not update staff permissions.'];
        }
    }   
}