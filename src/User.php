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

    /**
     * Registers a new account. Always signs up as 'borrower' — staff/admin
     * accounts should only ever be created via admin/users.php, never here,
     * or anyone could self-register as an admin through the public form.
     */
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

    /**
     * Logs in by email OR id_number — the sign-in form accepts either,
     * so a single $identifier is checked against both columns.
     */
    public function login(string $identifier, string $password): array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email OR id_number = :id_number');
        $stmt->execute([':email' => $identifier, ':id_number' => $identifier]);
        $user = $stmt->fetch();

        // Same generic message whether the identifier doesn't exist or the
        // password is wrong — don't reveal which one it was.
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email/ID or password.'];
        }

        // The users table stores last/first/middle name separately now —
        // rebuild the single display name the rest of the app expects.
        $fullName = trim(preg_replace(
            '/\s+/', ' ',
            ($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '')
        ));

        session_regenerate_id(true); // avoid session fixation across the privilege change
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
            LEFT JOIN staff_permissios sp ON u.user_id = sp.user_id 
            WHERE u.user_id = :user_id
        ");

        $stm->execute([':user_id' => $userId]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
    public function updateRole(int $userId, string $newRole): array {
        $allRoles = ['staff','admin', 'borrower'];

        if (!in_array($newRole, $allRoles, true)) {
            return ['success'=> false, 'message' => 'Invalid Role Specified'];
        }

        $stm = $this->db->prepare('UPDATE users SET role = :role WHERE user_id = :user_id');
        $stm->execute([
            ':user_id'=> $userId,
            ':role'=> $newRole
        ]);
        
        if ($newRole !== 'borrower') {
            $permStm = $this->db->prepare(
                "INSERT IGNORE INTO staff_permissions 
                (user_id, can_approve_requests, can_inspect_returns, can_confirm_pickup, can_manage_catalog)
                VALUES (:user_id, 0, 0, 0, 0)");
            $permStm->execute([':user_id' => $userId]);
        }
            
        return ['success' => true];
    }
}