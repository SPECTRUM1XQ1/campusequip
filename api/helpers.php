<?php
// Shared helpers for every API endpoint. Load this first in each endpoint file.
// No closing php tag on purpose: stray whitespace after it would count as output.

// Turn error display off BEFORE loading anything else, so a PHP notice can
// never be printed into the middle of a JSON response. Errors still go to
// the php_error_log file.
ini_set('display_errors', '0');

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/Permissions.php';

app_session_start();

header('Content-Type: application/json');

// Catches anything an endpoint forgot to catch, so the browser always gets
// JSON instead of an HTML error page. (This CALLS PHP's built-in
// set_exception_handler(); it must not be declared as a function.)
set_exception_handler(function (Throwable $e) {
    error_log('API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    json_response(['success' => false, 'message' => 'System error occurred. Please try again later.'], 500);
});

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit; // without this, the endpoint keeps running after it has already answered
}

// Every state-changing endpoint starts with this, so it is also where CSRF is enforced:
// a POST without the session's token (header X-CSRF-Token or field csrf_token) is refused.
function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
    }
    if (!csrf_verify()) {
        json_response(['success' => false, 'message' => 'Your session has expired. Please refresh the page and try again.'], 403);
    }
}

function current_user(): array
{
    if (!isset($_SESSION['user_id'])) {
        json_response(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    // Load fresh from the database each request, so a role or permission
    // that was just changed takes effect immediately.
    $user = (new User())->getById((int) $_SESSION['user_id']);

    if ($user === null) {
        json_response(['success' => false, 'message' => 'Your session is no longer valid.'], 401);
    }

    return $user;
}

function require_role(array $roles): array
{
    $user = current_user();

    if (!in_array($user['role'], $roles, true)) {
        json_response(['success' => false, 'message' => 'You do not have access to this action.'], 403);
    }

    return $user;
}

// $flag is one of: can_approve_requests, can_inspect_returns,
// can_confirm_pickup, can_manage_catalog
function require_permission(string $flag): array
{
    $user = current_user();

    // The rules live in Permissions (shared with the page guards). A typo in the
    // flag name must not silently deny everyone, so it is reported as a system error.
    try {
        $allowed = Permissions::allows($user, $flag);
    } catch (InvalidArgumentException $e) {
        error_log('require_permission: ' . $e->getMessage());
        json_response(['success' => false, 'message' => 'System error occurred. Please try again later.'], 500);
    }

    if ($allowed) {
        return $user; // admins always pass; staff pass when the job flag is set
    }

    json_response(['success' => false, 'message' => 'You do not have permission to do this.'], 403);
}

// "Reading" an input = taking the value out of $_POST. When the browser sends
// a form, PHP fills $_POST with one entry per field name. These helpers read
// one entry and clean it up, so endpoints stay short.
function post_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function post_int(string $key, int $default = 0): int
{
    $value = $_POST[$key] ?? $default;
    return is_scalar($value) ? (int) $value : $default;
}

function post_float(string $key, float $default = 0.0): float
{
    // $_POST values arrive as strings ("5.00"), never as real floats, so test
    // for a numeric string instead of is_float().
    $value = $_POST[$key] ?? $default;
    return is_numeric($value) ? (float) $value : $default;
}

// A group of checkboxes named "thing[]" arrives as an array of strings.
// Returns a de-duplicated list of positive ints; anything else is dropped.
function post_int_array(string $key): array
{
    $value = $_POST[$key] ?? [];
    if (!is_array($value)) {
        return [];
    }
    $ids = [];
    foreach ($value as $v) {
        if (is_scalar($v) && (int) $v > 0) {
            $ids[] = (int) $v;
        }
    }
    return array_values(array_unique($ids));
}

// Checkboxes: an unchecked box is not sent at all, so test whether the key exists.
function post_has(string $key): bool
{
    return isset($_POST[$key]);
}

function post_string_or_null(string $key): ?string
{
    $value = post_string($key);
    return $value === '' ? null : $value;
}