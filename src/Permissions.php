<?php
if (!defined('APP_INIT')) { http_response_code(403); exit; }

/**
 * The single definition of "who may do what". Both the page guards
 * (includes/auth_check.php) and the API guards (api/helpers.php) call this class,
 * so the rules can no longer drift apart.
 *
 *   admin  -> everything
 *   staff  -> only the job flags set in staff_permissions
 *   borrower -> nothing
 */
class Permissions
{
    public const FLAGS = [
        'can_approve_requests',
        'can_inspect_returns',
        'can_confirm_pickup',
        'can_manage_catalog',
    ];

    /** @param array|null $flagRow any array/row that may contain the can_* columns */
    public static function resolve(string $role, ?array $flagRow): array
    {
        if ($role === 'admin') {
            return array_fill_keys(self::FLAGS, true);
        }
        $out = array_fill_keys(self::FLAGS, false);
        if ($role === 'staff' && $flagRow) {
            foreach (self::FLAGS as $flag) {
                $out[$flag] = ((int) ($flagRow[$flag] ?? 0) === 1);
            }
        }
        return $out;
    }

    /** $user is a row from User::getById(). */
    public static function allows(array $user, string $flag): bool
    {
        if (!in_array($flag, self::FLAGS, true)) {
            throw new InvalidArgumentException("Unknown permission flag '{$flag}'");
        }
        return self::resolve((string) ($user['role'] ?? 'borrower'), $user)[$flag];
    }

    /** Reads one staff member's flags from the database (used by page guards). */
    public static function forUserId(PDO $db, string $role, int $userId): array
    {
        $row = null;
        if ($role === 'staff') {
            $stmt = $db->prepare('SELECT ' . implode(', ', self::FLAGS) . ' FROM staff_permissions WHERE user_id = :id');
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        return self::resolve($role, $row);
    }
}
