<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
/**
 * Global constants. Every protected file checks APP_INIT before running,
 * so this file (or index.php) must be the first thing loaded on any request.
 */

define('APP_NAME', 'CampusEquip');
define('APP_URL', 'http://localhost/campusequip'); // adjust if your htdocs folder name differs

// Fallback business rules — used until the System Settings module reads these
// from the database instead. Keep in sync with equipment_catalog defaults.
define('DEFAULT_MAX_LOAN_DAYS', 3);
define('DEFAULT_LATE_FINE_RATE', 5.00);

// Database credentials for local XAMPP development.
// Move these into a .env file (with vlucas/phpdotenv) before this ever
// leaves your machine — never commit real credentials to git.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'campusequipdb');
define('DB_USER', 'root');
define('DB_PASS', '');
