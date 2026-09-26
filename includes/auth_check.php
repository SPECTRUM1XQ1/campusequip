<?php
if (!defined('APP_INIT')) { http_response_code(403); exit; }

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Call at the very top of any page that requires a signed-in user,
 * before any HTML is output.
 */
function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/**
 * Call at the top of a role-restricted page.
 *   requireRole('admin')            -> admin only
 *   requireRole(['staff','admin'])  -> shared staff/admin pages
 */
function requireRole(string|array $roles): void
{
    requireLogin();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('403 — You do not have permission to view this page.');
    }
}
