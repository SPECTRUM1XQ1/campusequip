<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';

/**
 * Read-only borrowing report for the admin Export page.
 *
 * One row = one reservation, with its checkout (handover / return) and any fines joined in, so the
 * admin never has to pick a database table. Raw ids are replaced by names, and the page only ever
 * sends filter VALUES (borrower id, equipment id, status, dates), never table or column names.
 *
 * report() returns ['headers' => string[], 'rows' => string[][], 'total' => int, 'page' => int, 'pages' => int].
 */
class Export
{
    public const PER_PAGE = 10;

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

    public const HEADERS = [
        'Request', 'Borrower', 'Student / Employee ID', 'Equipment', 'Serial #', 'Status',
        'Requested At', 'Start', 'End', 'Checked Out', 'Due', 'Returned', 'Return Condition',
        'Fine (PHP)', 'Fine Status', 'Reviewed By', 'Decline Reason',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Options for the three dropdown filters. */
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
        return $value ? date('Y-m-d H:i', strtotime($value)) : '';
    }

    /**
     * $filters: borrower (user id), equipment (catalog id), status (key of STATUSES),
     *           from / to ("YYYY-MM-DD", compared with the date the request was made).
     * $page / $perPage: pass $perPage = 0 to get every matching row (Excel export, printing).
     */
    public function report(array $filters, int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['borrower'])) {
            $where[] = 'r.borrower_id = :borrower';
            $params[':borrower'] = (int) $filters['borrower'];
        }
        if (!empty($filters['equipment'])) {
            $where[] = 'r.catalog_id = :equipment';
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

        $from = "FROM reservations r
                 JOIN users b ON b.user_id = r.borrower_id
                 JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
                 LEFT JOIN serialized_items si ON si.item_id = r.item_id
                 LEFT JOIN checkouts c ON c.reservation_id = r.reservation_id
                 LEFT JOIN users rv ON rv.user_id = r.reviewed_by
                 LEFT JOIN (
                     SELECT checkout_id,
                            SUM(CASE WHEN payment_status <> 'waived' THEN amount_due ELSE 0 END) AS fine_total,
                            SUM(payment_status = 'unpaid') AS n_unpaid,
                            SUM(payment_status = 'paid')   AS n_paid,
                            SUM(payment_status = 'waived') AS n_waived
                     FROM fines GROUP BY checkout_id
                 ) fn ON fn.checkout_id = c.checkout_id";

        $count = $this->db->prepare("SELECT COUNT(*) $from $whereSql");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $perPage = max(0, $perPage);
        $pages   = $perPage > 0 ? max(1, (int) ceil($total / $perPage)) : 1;
        $page    = min(max(1, $page), $pages);

        $sql = "SELECT r.reservation_id, CONCAT(b.last_name, ', ', b.first_name) AS borrower, b.id_number,
                       ec.model_name, si.serial_number, r.status, r.requested_at, r.start_at, r.end_at,
                       r.decline_reason, r.reviewed_at,
                       CONCAT(rv.first_name, ' ', rv.last_name) AS reviewer,
                       c.checked_out_at, c.due_at, c.returned_at, c.return_condition,
                       fn.fine_total, fn.n_unpaid, fn.n_paid, fn.n_waived,
                       (r.status = 'checked_out' AND c.due_at < NOW() AND c.returned_at IS NULL) AS is_overdue
                $from $whereSql
                ORDER BY r.reservation_id DESC";
        if ($perPage > 0) {
            $sql .= ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage);
        }
        $stm = $this->db->prepare($sql);
        $stm->execute($params);

        $rows = [];
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $status = !empty($r['is_overdue']) ? 'Overdue' : ucfirst(str_replace('_', ' ', $r['status']));

            $fineStatus = '';
            if ($r['fine_total'] !== null) {
                if ((int) $r['n_unpaid'] > 0)      $fineStatus = 'Unpaid';
                elseif ((int) $r['n_paid'] > 0)    $fineStatus = 'Paid';
                elseif ((int) $r['n_waived'] > 0)  $fineStatus = 'Waived';
            }

            $rows[] = [
                'REQ-' . (int) $r['reservation_id'],
                $r['borrower'],
                (string) $r['id_number'],
                $r['model_name'],
                (string) $r['serial_number'],
                $status,
                self::dt($r['requested_at']),
                self::dt($r['start_at']),
                self::dt($r['end_at']),
                self::dt($r['checked_out_at']),
                self::dt($r['due_at']),
                self::dt($r['returned_at']),
                $r['return_condition'] ? ucfirst(str_replace('_', ' ', $r['return_condition'])) : '',
                $r['fine_total'] !== null ? number_format((float) $r['fine_total'], 2, '.', '') : '',
                $fineStatus,
                (string) $r['reviewer'],
                (string) $r['decline_reason'],
            ];
        }

        return [
            'headers' => self::HEADERS,
            'rows'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
        ];
    }
}
