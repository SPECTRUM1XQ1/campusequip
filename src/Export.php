<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Fine.php';
require_once __DIR__ . '/TransactionLog.php';

/**
 * Read-only, presentable versions of the main tables for the admin Export page.
 * Raw ids are replaced by names (borrower, equipment, staff) so the result reads
 * like a report instead of "SELECT * FROM reservations".
 *
 * get() returns ['title' => string, 'headers' => string[], 'rows' => string[][]].
 */
class Export
{
    public const TABLES = [
        'reservations' => 'Reservations',
        'checkouts'    => 'Checkouts',
        'fines'        => 'Fines',
        'logs'         => 'Transaction Logs',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** $from / $to: optional "YYYY-MM-DD" (inclusive). */
    public function get(string $table, ?string $from = null, ?string $to = null): array
    {
        switch ($table) {
            case 'checkouts':
                $data = $this->checkouts($from, $to);
                break;
            case 'fines':
                $data = $this->fines($from, $to);
                break;
            case 'logs':
                $data = $this->logs($from, $to);
                break;
            default:
                $table = 'reservations';
                $data = $this->reservations($from, $to);
        }
        return ['title' => self::TABLES[$table]] + $data;
    }

    private static function dt($value): string
    {
        return $value ? date('Y-m-d H:i', strtotime($value)) : '';
    }

    /** Builds "AND DATE(col) >= :from AND DATE(col) <= :to" and its parameters. */
    private static function range(string $column, ?string $from, ?string $to): array
    {
        $sql = '';
        $params = [];
        if ($from !== null) {
            $sql .= " AND DATE($column) >= :from_date";
            $params[':from_date'] = $from;
        }
        if ($to !== null) {
            $sql .= " AND DATE($column) <= :to_date";
            $params[':to_date'] = $to;
        }
        return [$sql, $params];
    }

    private function reservations(?string $from, ?string $to): array
    {
        [$where, $params] = self::range('r.requested_at', $from, $to);
        $stm = $this->db->prepare(
            "SELECT r.reservation_id, CONCAT(b.first_name, ' ', b.last_name) AS borrower, b.id_number,
                    ec.model_name, si.serial_number, r.start_at, r.end_at, r.purpose, r.status,
                    r.requested_at, CONCAT(rv.first_name, ' ', rv.last_name) AS reviewer, r.reviewed_at, r.decline_reason
             FROM reservations r
             JOIN users b ON b.user_id = r.borrower_id
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             LEFT JOIN serialized_items si ON si.item_id = r.item_id
             LEFT JOIN users rv ON rv.user_id = r.reviewed_by
             WHERE 1 = 1 $where
             ORDER BY r.reservation_id DESC"
        );
        $stm->execute($params);

        $rows = [];
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $rows[] = [
                'REQ-' . (int) $r['reservation_id'],
                $r['borrower'],
                (string) $r['id_number'],
                $r['model_name'],
                (string) $r['serial_number'],
                self::dt($r['start_at']),
                self::dt($r['end_at']),
                (string) $r['purpose'],
                ucfirst(str_replace('_', ' ', $r['status'])),
                self::dt($r['requested_at']),
                (string) $r['reviewer'],
                self::dt($r['reviewed_at']),
                (string) $r['decline_reason'],
            ];
        }
        return [
            'headers' => ['Request', 'Borrower', 'Student / Employee ID', 'Equipment', 'Serial #', 'Start', 'End', 'Purpose', 'Status', 'Requested At', 'Reviewed By', 'Reviewed At', 'Decline Reason'],
            'rows'    => $rows,
        ];
    }

    private function checkouts(?string $from, ?string $to): array
    {
        [$where, $params] = self::range('c.checked_out_at', $from, $to);
        $stm = $this->db->prepare(
            "SELECT c.checkout_id, c.reservation_id, CONCAT(b.first_name, ' ', b.last_name) AS borrower, b.id_number,
                    ec.model_name, si.serial_number, c.checked_out_at, c.due_at, c.returned_at,
                    CASE
                        WHEN c.returned_at IS NULL AND NOW() > c.due_at THEN 'Overdue'
                        WHEN c.returned_at IS NULL THEN 'Out'
                        WHEN c.returned_at > c.due_at THEN 'Returned late'
                        ELSE 'Returned'
                    END AS loan_status,
                    c.return_condition, c.damage_notes, c.inspection_remarks,
                    CONCAT(iu.first_name, ' ', iu.last_name) AS issued_by_name,
                    CONCAT(ru.first_name, ' ', ru.last_name) AS received_by_name
             FROM checkouts c
             JOIN reservations r ON r.reservation_id = c.reservation_id
             JOIN users b ON b.user_id = r.borrower_id
             JOIN serialized_items si ON si.item_id = c.item_id
             JOIN equipment_catalog ec ON ec.catalog_id = si.catalog_id
             LEFT JOIN users iu ON iu.user_id = c.issued_by
             LEFT JOIN users ru ON ru.user_id = c.received_by
             WHERE 1 = 1 $where
             ORDER BY c.checkout_id DESC"
        );
        $stm->execute($params);

        $rows = [];
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $rows[] = [
                'CO-' . (int) $r['checkout_id'],
                'REQ-' . (int) $r['reservation_id'],
                $r['borrower'],
                (string) $r['id_number'],
                $r['model_name'],
                (string) $r['serial_number'],
                self::dt($r['checked_out_at']),
                self::dt($r['due_at']),
                self::dt($r['returned_at']),
                $r['loan_status'],
                $r['return_condition'] ? ucfirst(str_replace('_', ' ', $r['return_condition'])) : '',
                trim((string) $r['damage_notes'] . ' ' . (string) $r['inspection_remarks']),
                (string) $r['issued_by_name'],
                (string) $r['received_by_name'],
            ];
        }
        return [
            'headers' => ['Checkout', 'Request', 'Borrower', 'Student / Employee ID', 'Equipment', 'Serial #', 'Checked Out', 'Due', 'Returned', 'Status', 'Return Condition', 'Notes', 'Handed Over By', 'Received By'],
            'rows'    => $rows,
        ];
    }

    private function fines(?string $from, ?string $to): array
    {
        $rows = [];
        foreach ((new Fine())->listAllDetailed() as $f) {
            $date = $f['charged_at'] ? date('Y-m-d', strtotime($f['charged_at'])) : null;
            if ($from !== null && ($date === null || $date < $from)) continue;
            if ($to !== null && ($date === null || $date > $to)) continue;

            $rows[] = [
                'FINE-' . (int) $f['fine_id'],
                'REQ-' . (int) $f['reservation_id'],
                $f['borrower_name'],
                (string) $f['borrower_id_number'],
                $f['model_name'],
                (string) $f['serial_number'],
                number_format((float) $f['amount_due'], 2, '.', ''),
                ucfirst($f['payment_status']),
                self::dt($f['charged_at']),
                (string) $f['reason'],
                self::dt($f['paid_at']),
            ];
        }
        return [
            'headers' => ['Fine', 'Request', 'Borrower', 'Student / Employee ID', 'Equipment', 'Serial #', 'Amount (PHP)', 'Status', 'Charged At', 'Reason', 'Paid At'],
            'rows'    => $rows,
        ];
    }

    private function logs(?string $from, ?string $to): array
    {
        $rows = [];
        foreach ((new TransactionLog())->listAll($from, $to) as $log) {
            $rows[] = [
                'LOG-' . (int) $log['log_id'],
                self::dt($log['action_timestamp']),
                $log['reservation_id'] ? 'REQ-' . (int) $log['reservation_id'] : '',
                (string) ($log['serial_snapshot'] ?? ''),
                ucfirst(str_replace('_', ' ', $log['action_type'])),
                (string) ($log['borrower_name'] ?? ''),
                (string) $log['actor_name'],
                $log['fine_amount'] !== null ? number_format((float) $log['fine_amount'], 2, '.', '') : '',
                (string) ($log['remarks'] ?? ''),
            ];
        }
        return [
            'headers' => ['Log', 'Timestamp', 'Request', 'Serial #', 'Action', 'Borrower', 'Staff', 'Fine (PHP)', 'Remarks'],
            'rows'    => $rows,
        ];
    }
}
