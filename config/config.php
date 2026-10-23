<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
/**
 * Global constants. Every protected file checks APP_INIT before running,
 * so this file (or index.php) must be the first thing loaded on any request.
 */

define('APP_NAME', 'LendLab');
// The ONLY place the folder name appears. If your htdocs folder is not called
// "campusequip", change this one line (every link, redirect and fetch() reads it).
// Pages use APP_BASE; JavaScript reads it from window.APP_BASE (see app-core.js).
define('APP_BASE', '/campusequip');
// Browser-facing pages live under public/; API endpoints live beside it in api/.
define('APP_URL', APP_BASE . '/public');
define('API_URL', APP_BASE . '/api');
// Fallback business rules — used until the System Settings module reads these
// from the database instead. Keep in sync with equipment_catalog defaults.
define('DEFAULT_MAX_LOAN_DAYS', 3);
define('DEFAULT_LATE_FINE_RATE', 5.00);
// How long a borrower has to collect an approved item, counted from the
// reservation start date (it never runs past the loan's end date).
define('PICKUP_WINDOW_DAYS', 3);
// A loan always ends on its last day at this time (24h clock). Storing a bare date
// meant "due at 00:00", which made anyone returning DURING the last day late.
define('LOAN_DUE_TIME', '17:00:00');

// Database credentials for local XAMPP development.
// Move these into a .env file (with vlucas/phpdotenv) before this ever
// leaves your machine — never commit real credentials to git.
// Each value can be overridden with an environment variable (used by tests/ so they
// never touch the real database).
define('DB_HOST', getenv('CAMPUSEQUIP_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('CAMPUSEQUIP_DB') ?: 'campusequipdb');
define('DB_USER', getenv('CAMPUSEQUIP_DB_USER') ?: 'root');
define('DB_PASS', getenv('CAMPUSEQUIP_DB_PASS') !== false ? getenv('CAMPUSEQUIP_DB_PASS') : '');

require_once __DIR__ . '/session.php';
