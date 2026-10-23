<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Reservation.php';

/**
 * Admin-editable messages and switches (table system_settings, one row per key), plus the
 * read-only numbers shown on the System Settings page.
 *
 * Reading never breaks a page: if the table is missing or empty the defaults below are used.
 */
class Settings
{
    public const DEFAULTS = [
        'maintenance_mode'    => '0',
        'maintenance_message' => 'LendLab is under maintenance. New reservations are paused; please check back soon.',
        'announcement'        => '',
        'announcement_staff'  => '',
        'desk_hours'          => '',
        'desk_contact'        => '',
    ];

    private const MAX_LENGTH = [
        'maintenance_message' => 200,
        'announcement'        => 300,
        'announcement_staff'  => 300,
        'desk_hours'          => 100,
        'desk_contact'        => 100,
    ];

    private const LABELS = [
        'maintenance_message' => 'Maintenance message',
        'announcement'        => 'Announcement for borrowers',
        'announcement_staff'  => 'Announcement for staff',
        'desk_hours'          => 'Desk hours',
        'desk_contact'        => 'Desk contact',
    ];

    private static ?array $cache = null;

    /** Every setting, defaults filled in. */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $values = self::DEFAULTS;
        try {
            $rows = Database::getConnection()->query('SELECT setting_key, setting_value FROM system_settings')->fetchAll();
            foreach ($rows as $row) {
                if (array_key_exists($row['setting_key'], self::DEFAULTS)) {
                    $values[$row['setting_key']] = (string) $row['setting_value'];
                }
            }
        } catch (PDOException $e) {
            error_log('Settings: could not read system_settings, using defaults (' . $e->getMessage() . ')');
        }

        self::$cache = $values;
        return $values;
    }

    public static function get(string $key): string
    {
        return self::all()[$key] ?? '';
    }

    public static function isMaintenance(): bool
    {
        return self::get('maintenance_mode') === '1';
    }

    /** Message borrowers see (and get back from the API) while maintenance mode is on. */
    public static function maintenanceMessage(): string
    {
        $msg = trim(self::get('maintenance_message'));
        return $msg !== '' ? $msg : self::DEFAULTS['maintenance_message'];
    }

    private static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    /** One line, no control characters. */
    private static function clean(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value) ?? '';
        return trim($value);
    }

    /**
     * Saves any of the known keys present in $input. Unknown keys are ignored.
     * Returns ['success' => bool, 'message' => string].
     */
    public function save(array $input, int $adminId): array
    {
        $toSave = [];

        foreach (self::DEFAULTS as $key => $default) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $value = is_string($input[$key]) ? self::clean($input[$key]) : '';

            if ($key === 'maintenance_mode') {
                if ($value !== '0' && $value !== '1') {
                    return ['success' => false, 'message' => 'Maintenance mode must be on or off.'];
                }
            } else {
                if (self::length($value) > self::MAX_LENGTH[$key]) {
                    return ['success' => false, 'message' => self::LABELS[$key] . ' must be ' . self::MAX_LENGTH[$key] . ' characters or fewer.'];
                }
                if ($key === 'maintenance_message' && $value === '') {
                    $value = $default;
                }
            }
            $toSave[$key] = $value;
        }

        if ($toSave === []) {
            return ['success' => false, 'message' => 'Nothing to save.'];
        }

        $db = Database::getConnection();
        try {
            $db->beginTransaction();
            $stm = $db->prepare(
                'INSERT INTO system_settings (setting_key, setting_value, updated_by)
                 VALUES (:k, :v, :u)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
            );
            foreach ($toSave as $key => $value) {
                $stm->execute([':k' => $key, ':v' => $value, ':u' => $adminId]);
            }
            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Settings::save failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Could not save. Import database/migrations/2026-10-24_system_settings.sql first if you have not yet.',
            ];
        }

        self::$cache = null;
        return ['success' => true, 'message' => 'Settings saved.'];
    }

    /**
     * Live counts for the System Settings page ("System status" and "Needs attention").
     * Lapsed pick-ups are expired first so the numbers match what staff see.
     */
    public function snapshot(): array
    {
        (new Reservation())->expireLapsedPickups();

        $row = Database::getConnection()->query(
            "SELECT
               (SELECT COUNT(*) FROM users)                                                   AS users,
               (SELECT COUNT(*) FROM equipment_catalog WHERE is_archived = 0)                 AS models,
               (SELECT COUNT(*) FROM serialized_items WHERE status <> 'retired')              AS units,
               (SELECT COUNT(*) FROM checkouts WHERE returned_at IS NULL)                     AS active_loans,
               (SELECT COUNT(*) FROM reservations WHERE status = 'pending')                   AS pending,
               (SELECT COUNT(*) FROM reservations WHERE status = 'approved')                  AS ready,
               (SELECT COUNT(*) FROM checkouts WHERE returned_at IS NULL AND due_at < NOW())  AS overdue,
               (SELECT COUNT(*) FROM fines WHERE payment_status = 'unpaid')                   AS unpaid_fines,
               (SELECT COALESCE(SUM(amount_due), 0) FROM fines WHERE payment_status = 'unpaid') AS unpaid_total"
        )->fetch();

        return $row ?: [];
    }
}
