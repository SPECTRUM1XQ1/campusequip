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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
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

    // A typo in the flag name would otherwise silently deny everyone.
    if (!array_key_exists($flag, $user)) {
        error_log("require_permission: unknown permission flag '{$flag}'");
        json_response(['success' => false, 'message' => 'System error occurred. Please try again later.'], 500);
    }

    if ($user['role'] === 'admin') {
        return $user; // admins bypass every permission check
    }

    if ($user['role'] === 'staff' && (int) $user[$flag] === 1) {
        return $user;
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

// Checkboxes: an unchecked box is not sent at all, so test whether the key exists.
function post_has(string $key): bool
{
    return isset($_POST[$key]);
}