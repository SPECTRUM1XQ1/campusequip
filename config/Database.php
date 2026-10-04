<?php
if (!defined('APP_INIT')) { http_response_code(403); exit; }

/**
 * Single shared PDO connection for the whole request.
 * Usage: $db = Database::getConnection();
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            try {
                self::$instance->exec("SET time_zone = '+08:00'");
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
                ]);
            } catch (PDOException $e) {
                // Never echo $e->getMessage() to the browser — it can contain
                // host/user details. Log it server-side, show a generic error.
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die(json_encode(['success' => false, 'message' => 'Server error. Please try again later.']));
            }
        }
        return self::$instance;
    }
}
