<?php
if (!defined('APP_INIT')) { http_response_code(403); exit; }

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Fine.php';

/**
 * Everything the two dashboards show. The pages used to run their own SQL;
 * now they only ask this class and print the result.
 */
class Dashboard
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** KPI numbers for the staff/admin dashboard. $withFines = may this user see money? */
    public function adminStats(bool $withFines): array
    {
        $count = fn(string $sql): int => (int) $this->db->query($sql)->fetchColumn();

        $stats = [
            'pendingRequests'    => $count("SELECT COUNT(*) FROM reservations WHERE status = 'pending'"),
            'activeLoans'        => $count("SELECT COUNT(*) FROM checkouts WHERE returned_at IS NULL"),
            'overdueItems'       => $count("SELECT COUNT(*) FROM checkouts WHERE returned_at IS NULL AND due_at < NOW()"),
            'availableEquipment' => $count("SELECT COUNT(*) FROM serialized_items WHERE status = 'available'"),
            'unpaidFines'        => ['total' => 0, 'count' => 0],
        ];

        if ($withFines) {
            $stats['unpaidFines'] = $this->db->query(
                "SELECT COALESCE(SUM(amount_due), 0) AS total, COUNT(*) AS count FROM fines WHERE payment_status = 'unpaid'"
            )->fetch(PDO::FETCH_ASSOC);
        }
        return $stats;
    }

    /** Lists and fine totals for one borrower's home page. */
    public function borrowerSummary(int $userId): array
    {
        $rows = function (string $sql) use ($userId): array {
            $stm = $this->db->prepare($sql);
            $stm->execute([':uid' => $userId]);
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        };

        $myPending = $rows(
            "SELECT r.*, ec.model_name
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             WHERE r.borrower_id = :uid AND r.status = 'pending'
             ORDER BY r.requested_at DESC"
        );

        $myApproved = $rows(
            "SELECT r.*, ec.model_name, si.serial_number
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             JOIN serialized_items si ON si.item_id = r.item_id
             WHERE r.borrower_id = :uid AND r.status = 'approved'
             ORDER BY r.reviewed_at DESC"
        );

        $myCheckouts = $rows(
            "SELECT c.*, ec.model_name, ec.late_fine_rate, si.serial_number,
                    (NOW() > c.due_at) AS is_overdue,
                    CASE WHEN NOW() > c.due_at THEN GREATEST(1, CEIL(TIMESTAMPDIFF(SECOND, c.due_at, NOW()) / 86400)) ELSE 0 END AS days_late
             FROM checkouts c
             JOIN reservations r ON r.reservation_id = c.reservation_id
             JOIN serialized_items si ON si.item_id = c.item_id
             JOIN equipment_catalog ec ON ec.catalog_id = si.catalog_id
             WHERE r.borrower_id = :uid AND c.returned_at IS NULL"
        );

        // Fines: running late fines on overdue loans + charged fines that are still unpaid.
        $fineModel = new Fine();
        $accruing  = $fineModel->listAccruingForBorrower($userId);
        $totals    = Fine::totals($fineModel->listForBorrower($userId), $accruing);

        return [
            'myPending'     => $myPending,
            'myApproved'    => $myApproved,
            'myCheckouts'   => $myCheckouts,
            'accruing'      => $accruing,
            'accruingTotal' => $totals['accruingTotal'],
            'unpaidTotal'   => $totals['unpaidTotal'],
            'unpaidCount'   => $totals['unpaidCount'],
            'paidTotal'     => $totals['paidTotal'],
            'paidCount'     => $totals['paidCount'],
            'owedTotal'     => $totals['accruingTotal'] + $totals['unpaidTotal'],
        ];
    }
}
