<?php
// Page guard. Every protected page loads this first (after config/config.php).
// RECONSTRUCTED: the original includes/auth_check.php was missing from the upload,
// so compare this with your own copy/backup if you have one.
if (!defined('APP_INIT')) { http_response_code(403); exit; }

require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/Permissions.php';
require_once __DIR__ . '/helpers/view-helpers.php';   // e(), js_arg()

app_session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/login/login.php');
    exit;
}

/** The signed-in user, loaded fresh each request so role/permission changes apply immediately. */
function authUser(): array
{
    static $user = null;
    if ($user === null) {
        $user = (new User())->getById((int) $_SESSION['user_id']);
        if ($user === null) {                       // account deleted while signed in
            $_SESSION = [];
            session_destroy();
            header('Location: ' . APP_URL . '/login/login.php');
            exit;
        }
        $_SESSION['role'] = $user['role'];
    }
    return $user;
}

function homeFor(string $role): string
{
    return in_array($role, ['staff', 'admin'], true)
        ? APP_BASE . '/public/manage/dashboard.php'
        : APP_BASE . '/public/borrower/dashboard.php';
}

/** $roles: 'admin' or ['admin','staff']. Wrong role -> sent to that role's own home page. */
function requireRole($roles): void
{
    $user = authUser();
    if (!in_array($user['role'], (array) $roles, true)) {
        header('Location: ' . homeFor($user['role']));
        exit;
    }
}

function hasPermission(string $flag): bool
{
    return Permissions::allows(authUser(), $flag);
}

/** $flags: one flag or a list; passing ANY one of them is enough (admins pass everything). */
function requirePermission($flags): void
{
    foreach ((array) $flags as $flag) {
        if (hasPermission($flag)) {
            return;
        }
    }
    header('Location: ' . homeFor(authUser()['role']));
    exit;
}
