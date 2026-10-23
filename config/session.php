<?php
if (!defined('APP_INIT')) { http_response_code(403); exit; }

/**
 * One place that starts the session, so every entry point (pages, API, login)
 * gets the same hardened cookie settings. Also holds the CSRF helpers.
 */
function app_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.use_strict_mode', '1');   // refuse session ids the server did not create
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,   // true automatically once the site is served over HTTPS
        'httponly' => true,     // JavaScript cannot read the cookie
        'samesite' => 'Lax',    // not sent on cross-site POSTs (second line of CSRF defence)
    ]);
    session_start();
}

/**
 * CSRF token: a random secret kept in the session. Every browser POST must send it back
 * (header X-CSRF-Token, added automatically by app-core.js, or a csrf_token field).
 * A malicious site cannot read it, so it cannot forge a request that carries it.
 */
function csrf_token(): string
{
    app_session_start();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $sent = null): bool
{
    app_session_start();
    $sent ??= $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    $real = $_SESSION['csrf_token'] ?? '';
    return is_string($sent) && is_string($real) && $real !== '' && hash_equals($real, $sent);
}
