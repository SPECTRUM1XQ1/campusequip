<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';

/**
 * Read-only transaction-log report for the admin Export page.
 *
 * One row = one entry of the transaction log (a request made, approved, declined, a handover, a return, a fine
 * charged / paid / waived ...), with the borrower, the equipment model, the serial number, who did it and the
 * fine amount joined in, so the admin never has to pick a database table. Raw ids are replaced by names, and the
 * page only ever sends filter VALUES (borrower id, equipment id, status, dates), never table or column names.
 *
 * The filters work on the request a log entry belongs to: its borrower, its equipment model, its current status
 * and the date it was requested. Entries that belong to no request are only shown when no filter is set.
 *
 * report() returns ['headers' => string[], 'rows' => string[][], 'total' => int]. Every matching row is returned;
 * the page cuts them into pages in the browser (assets/js/filters.js).
 */
class Export
{
    /** Status dropdown: key sent by the page => label shown. "overdue" is worked out from the due date. */
    public const STATUSES = [
        'pending'     => 'Pending',
        'approved'    => 'Approved (awaiting pick-up)',
        'checked_out' => 'Checked out',
        'overdue'     => 'Overdue',
        'returned'    => 'Returned',
        'declined'    => 'Declined',
        'cancelled'   => 'Cancelled',
    ];

    /** Column positions in every row (the page and the printable report use these). */
    public const COL_LOG = 0, COL_TIME = 1, COL_REQ = 2, COL_BORROWER = 3, COL_ID = 4, COL_EQUIPMENT = 5,
                 COL_SERIAL = 6, COL_ACTION = 7, COL_ACTOR = 8, COL_FINE = 9, COL_REQ_STATUS = 10, COL_REMARKS = 11;

    public const HEADERS = [
        'Log ID', 'Timestamp', 'Request', 'Borrower', 'Student / Employee ID', 'Equipment', 'Serial #',
        'Action', 'Performed By', 'Fine (PHP)', 'Request Status', 'Remarks / Notes',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Options for the three filters. */
    public function filterOptions(): array
    {
        $borrowers = [];
        $stm = $this->db->query(
            "SELECT DISTINCT u.user_id, u.last_name, u.first_name, u.id_number
             FROM users u JOIN reservations r ON r.borrower_id = u.user_id
             ORDER BY u.last_name, u.first_name"
        );
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $label = $u['last_name'] . ', ' . $u['first_name'];
            if ($u['id_number'] !== null && $u['id_number'] !== '') {
                $label .= ' (' . $u['id_number'] . ')';
            }
            $borrowers[(int) $u['user_id']] = $label;
        }

        $equipment = [];
        $stm = $this->db->query("SELECT catalog_id, model_name FROM equipment_catalog ORDER BY model_name");
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $e) {
            $equipment[(int) $e['catalog_id']] = $e['model_name'];
        }

        return ['borrowers' => $borrowers, 'equipment' => $equipment, 'statuses' => self::STATUSES];
    }

    private static function dt($value): string
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
    }

    /**
     * $filters: borrower (user id), equipment (catalog id), status (key of STATUSES),
     *           from / to ("YYYY-MM-DD", compared with the date the request was made).
     */
    public function report(array $filters): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['borrower'])) {
            $where[] = 'r.borrower_id = :borrower';
            $params[':borrower'] = (int) $filters['borrower'];
        }
        if (!empty($filters['equipment'])) {
            // the request's model, or (for an entry about a unit only) the unit's model
            $where[] = 'COALESCE(r.catalog_id, si.catalog_id) = :equipment';
            $params[':equipment'] = (int) $filters['equipment'];
        }
        if (!empty($filters['status']) && isset(self::STATUSES[$filters['status']])) {
            if ($filters['status'] === 'overdue') {
                $where[] = "r.status = 'checked_out' AND c.due_at < NOW() AND c.returned_at IS NULL";
            } else {
                $where[] = 'r.status = :status';
                $params[':status'] = $filters['status'];
            }
        }
        if (!empty($filters['from'])) {
            $where[] = 'DATE(r.requested_at) >= :from_date';
            $params[':from_date'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'DATE(r.requested_at) <= :to_date';
            $params[':to_date'] = $filters['to'];
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT tl.log_id, tl.action_timestamp, tl.reservation_id, tl.action_type, tl.remarks,
                       COALESCE(tl.serial_snapshot, si.serial_number) AS serial_number,
                       COALESCE(CONCAT(a.first_name, ' ', a.last_name), 'System') AS actor,
                       CONCAT(b.last_name, ', ', b.first_name) AS borrower, b.id_number,
                       ec.model_name, r.status AS req_status, f.amount_due AS fine_amount,
                       (r.status = 'checked_out' AND c.due_at < NOW() AND c.returned_at IS NULL) AS is_overdue
                FROM transaction_logs tl
                LEFT JOIN users a ON a.user_id = tl.actor_id
                LEFT JOIN reservations r ON r.reservation_id = tl.reservation_id
                LEFT JOIN users b ON b.user_id = r.borrower_id
                LEFT JOIN serialized_items si ON si.item_id = tl.item_id
                LEFT JOIN equipment_catalog ec ON ec.catalog_id = COALESCE(r.catalog_id, si.catalog_id)
                LEFT JOIN checkouts c ON c.reservation_id = r.reservation_id
                LEFT JOIN fines f ON f.fine_id = tl.fine_id
                $whereSql
                ORDER BY tl.action_timestamp DESC, tl.log_id DESC";
        $stm = $this->db->prepare($sql);
        $stm->execute($params);

        $rows = [];
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $reqStatus = '';
            if ($r['req_status'] !== null) {
                $reqStatus = !empty($r['is_overdue']) ? 'Overdue' : ucfirst(str_replace('_', ' ', $r['req_status']));
            }

            $rows[] = [
                'LOG-' . (int) $r['log_id'],
                self::dt($r['action_timestamp']),
                $r['reservation_id'] ? 'REQ-' . (int) $r['reservation_id'] : '',
                (string) $r['borrower'],
                (string) $r['id_number'],
                (string) $r['model_name'],
                (string) $r['serial_number'],
                ucfirst(str_replace('_', ' ', $r['action_type'])),
                (string) $r['actor'],
                $r['fine_amount'] !== null ? number_format((float) $r['fine_amount'], 2, '.', '') : '',
                $reqStatus,
                (string) $r['remarks'],
            ];
        }

        return [
            'headers' => self::HEADERS,
            'rows'    => $rows,
            'total'   => count($rows),
        ];
    }
}
