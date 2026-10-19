<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/TransactionLog.php';

class Fine
{
    private PDO $db;
    private TransactionLog $logger;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->logger = new TransactionLog();
    }

    /** Kinds of fine. 'late' is normally created automatically at return; staff add the others. */
    public const TYPES = ['late', 'damage', 'missing_accessories', 'other'];
    public const MANUAL_TYPES = ['damage', 'missing_accessories', 'other'];

    /**
     * A loan can carry several fines (for example a late fine AND a damage fine). Each fine is
     * independent: it has its own type, reason and payment status.
     */
    public function charge(int $checkoutId, int $staffId, float $amount, ?string $reason = null, string $type = 'other'): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'The amount must be greater than 0.'];
        }
        if (!in_array($type, self::TYPES, true)) {
            return ['success' => false, 'message' => 'Unknown fine type.'];
        }
        // Keep at most 255 characters (the column size) without needing the mbstring extension.
        $reason = ($reason !== null && trim($reason) !== '' && preg_match('/^.{1,255}/us', trim($reason), $cut)) ? $cut[0] : null;

        try {
            $this->db->beginTransaction();

            // Lock the checkout row. It also gives us the reservation_id and
            // item_id the log needs (a fine only knows its checkout_id).
            $stm = $this->db->prepare('SELECT reservation_id, item_id FROM checkouts WHERE checkout_id = :checkout_id FOR UPDATE');
            $stm->execute([':checkout_id' => $checkoutId]);
            $checkout = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$checkout) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Checkout not found.'];
            }

            // A loan is only late once, so there is at most one (non-waived) late fine per checkout.
            if ($type === 'late') {
                $stm = $this->db->prepare("SELECT fine_id FROM fines WHERE checkout_id = :checkout_id AND fine_type = 'late' AND payment_status <> 'waived' LIMIT 1");
                $stm->execute([':checkout_id' => $checkoutId]);
                if ($stm->fetch()) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'This loan already has a late fine.'];
                }
            }

            // Guard against a double-click / double-submit: the identical fine in the last minute is refused.
            $stm = $this->db->prepare(
                "SELECT fine_id FROM fines
                 WHERE checkout_id = :checkout_id AND fine_type = :type AND amount_due = :amount
                   AND reason <=> :reason AND created_at >= (NOW() - INTERVAL 1 MINUTE)
                 LIMIT 1"
            );
            $stm->execute([':checkout_id' => $checkoutId, ':type' => $type, ':amount' => $amount, ':reason' => $reason]);
            if ($stm->fetch()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'This exact fine was just charged. Reload the page to see it.'];
            }

            $stm = $this->db->prepare(
                "INSERT INTO fines (checkout_id, amount_due, fine_type, reason, payment_status, created_at)
                 VALUES (:checkout_id, :amount_due, :type, :reason, 'unpaid', NOW())"
            );
            $stm->execute([
                ':checkout_id' => $checkoutId,
                ':amount_due'  => $amount,
                ':type'        => $type,
                ':reason'      => $reason,
            ]);
            $fineId = (int) $this->db->lastInsertId();

            $this->logger->record(
                (int) $checkout['reservation_id'],
                (int) $checkout['item_id'],
                $fineId,
                $staffId,
                'fine_charged',
                'Fine charged: ' . number_format($amount, 2) . ($reason !== null ? ' - ' . $reason : '')
            );

            $this->db->commit();
            return ['success' => true, 'fine_id' => $fineId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Fine::charge failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not charge the fine. Please try again.'];
        }
    }

    public function markPaid(int $fineId, int $staffId): array
    {
        try {
            $this->db->beginTransaction();

            $fine = $this->lockFine($fineId);

            if ($fine === null) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Fine record does not exist.'];
            }
            if ($fine['payment_status'] !== 'unpaid') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Fine has already been settled.'];
            }

            $stm = $this->db->prepare("UPDATE fines SET payment_status = 'paid', paid_at = NOW() WHERE fine_id = :fine_id");
            $stm->execute([':fine_id' => $fineId]);

            $this->logger->record(
                (int) $fine['reservation_id'],
                (int) $fine['item_id'],
                $fineId,
                $staffId,
                'fine_paid',
                'Fine of ' . $fine['amount_due'] . ' paid'
            );

            $this->db->commit();
            return ['success' => true, 'fine_id' => $fineId, 'message' => 'Fine paid successfully.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Fine::markPaid failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not record the payment. Please try again.'];
        }
    }

    public function waive(int $fineId, int $staffId, ?string $reason = null): array
    {
        try {
            $this->db->beginTransaction();

            $fine = $this->lockFine($fineId);

            if ($fine === null) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Fine record does not exist.'];
            }
            if ($fine['payment_status'] !== 'unpaid') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Fine has already been settled.'];
            }

            $stm = $this->db->prepare("UPDATE fines SET payment_status = 'waived' WHERE fine_id = :fine_id");
            $stm->execute([':fine_id' => $fineId]);

            $remarks = 'Fine of ' . $fine['amount_due'] . ' waived';
            if (!empty($reason)) {
                $remarks .= ': ' . $reason;
            }

            $this->logger->record(
                (int) $fine['reservation_id'],
                (int) $fine['item_id'],
                $fineId,
                $staffId,
                'fine_waived',
                $remarks
            );

            $this->db->commit();
            return ['success' => true, 'fine_id' => $fineId, 'message' => 'Fine waived successfully.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Fine::waive failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not waive the fine. Please try again.'];
        }
    }

    public function getById(int $fineId): ?array
    {
        $stm = $this->db->prepare(
            'SELECT f.*, c.reservation_id, r.borrower_id
             FROM fines f
             INNER JOIN checkouts c ON c.checkout_id = f.checkout_id
             INNER JOIN reservations r ON r.reservation_id = c.reservation_id
             WHERE f.fine_id = :fine_id'
        );
        $stm->execute([':fine_id' => $fineId]);
        return $stm->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getByCheckout(int $checkoutId): array
    {
        $stm = $this->db->prepare(
            'SELECT f.*, c.item_id, r.borrower_id
             FROM fines f
             INNER JOIN checkouts c ON c.checkout_id = f.checkout_id
             INNER JOIN reservations r ON r.reservation_id = c.reservation_id
             WHERE f.checkout_id = :checkout_id
             ORDER BY f.payment_status ASC, f.fine_id DESC'
        );
        $stm->execute([':checkout_id' => $checkoutId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByReservation(int $reservationId): array
    {
        $stm = $this->db->prepare(
            'SELECT f.*, c.reservation_id, r.borrower_id
             FROM fines f
             INNER JOIN checkouts c ON c.checkout_id = f.checkout_id
             INNER JOIN reservations r ON r.reservation_id = c.reservation_id
             WHERE c.reservation_id = :reservation_id
             ORDER BY f.payment_status ASC, f.fine_id DESC'
        );
        $stm->execute([':reservation_id' => $reservationId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listUnpaid(): array
    {
        $stm = $this->db->query(
            "SELECT f.*, r.borrower_id, si.serial_number, si.storage_location
             FROM fines f
             INNER JOIN checkouts c ON c.checkout_id = f.checkout_id
             INNER JOIN reservations r ON r.reservation_id = c.reservation_id
             LEFT JOIN serialized_items si ON si.item_id = c.item_id
             WHERE f.payment_status = 'unpaid'
             ORDER BY f.fine_id DESC"
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Every fine with who/what it is for, newest first. charged_at and reason
     * come from the fine_charged row of the transaction log (the log is the
     * permanent record of when and why).
     */
    public function listAllDetailed(): array
    {
        $stm = $this->db->query(
            "SELECT f.fine_id, f.checkout_id, f.amount_due, f.fine_type, f.payment_status, f.paid_at,
                    c.reservation_id, c.due_at, c.returned_at,
                    si.serial_number, ec.model_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS borrower_name,
                    u.id_number AS borrower_id_number,
                    COALESCE(f.created_at, (SELECT MIN(tl.action_timestamp) FROM transaction_logs tl
                      WHERE tl.fine_id = f.fine_id AND tl.action_type = 'fine_charged')) AS charged_at,
                    COALESCE(f.reason, (SELECT tl.remarks FROM transaction_logs tl
                      WHERE tl.fine_id = f.fine_id AND tl.action_type = 'fine_charged'
                      ORDER BY tl.log_id ASC LIMIT 1)) AS reason
             FROM fines f
             JOIN checkouts c ON c.checkout_id = f.checkout_id
             JOIN reservations r ON r.reservation_id = c.reservation_id
             JOIN serialized_items si ON si.item_id = c.item_id
             JOIN equipment_catalog ec ON ec.catalog_id = si.catalog_id
             JOIN users u ON u.user_id = r.borrower_id
             ORDER BY f.fine_id DESC"
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Overdue loans that are still out. Their late fine is not charged yet (it is
     * charged automatically when the item is returned), so this shows the running amount.
     */
    public function listAccruing(): array
    {
        $stm = $this->db->query(
            "SELECT c.checkout_id, c.reservation_id, c.due_at,
                    si.serial_number, ec.model_name, ec.late_fine_rate,
                    CONCAT(u.first_name, ' ', u.last_name) AS borrower_name,
                    u.id_number AS borrower_id_number,
                    GREATEST(1, CEIL(TIMESTAMPDIFF(SECOND, c.due_at, NOW()) / 86400)) AS days_late
             FROM checkouts c
             JOIN reservations r ON r.reservation_id = c.reservation_id
             JOIN serialized_items si ON si.item_id = c.item_id
             JOIN equipment_catalog ec ON ec.catalog_id = si.catalog_id
             JOIN users u ON u.user_id = r.borrower_id
             WHERE c.returned_at IS NULL AND NOW() > c.due_at
             ORDER BY c.due_at ASC"
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /** The signed-in borrower's own fines (charged ones), newest first. Same shape as listAllDetailed(). */
    public function listForBorrower(int $borrowerId): array
    {
        $stm = $this->db->prepare(
            "SELECT f.fine_id, f.checkout_id, f.amount_due, f.fine_type, f.payment_status, f.paid_at,
                    c.reservation_id, c.due_at, c.returned_at,
                    si.serial_number, ec.model_name,
                    COALESCE(f.created_at, (SELECT MIN(tl.action_timestamp) FROM transaction_logs tl
                      WHERE tl.fine_id = f.fine_id AND tl.action_type = 'fine_charged')) AS charged_at,
                    COALESCE(f.reason, (SELECT tl.remarks FROM transaction_logs tl
                      WHERE tl.fine_id = f.fine_id AND tl.action_type = 'fine_charged'
                      ORDER BY tl.log_id ASC LIMIT 1)) AS reason
             FROM fines f
             JOIN checkouts c ON c.checkout_id = f.checkout_id
             JOIN reservations r ON r.reservation_id = c.reservation_id
             JOIN serialized_items si ON si.item_id = c.item_id
             JOIN equipment_catalog ec ON ec.catalog_id = si.catalog_id
             WHERE r.borrower_id = :uid
             ORDER BY f.fine_id DESC"
        );
        $stm->execute([':uid' => $borrowerId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /** The borrower's overdue loans that are still out, with the running late fine (charged on return). */
    public function listAccruingForBorrower(int $borrowerId): array
    {
        $stm = $this->db->prepare(
            "SELECT c.checkout_id, c.reservation_id, c.due_at,
                    si.serial_number, ec.model_name, ec.late_fine_rate,
                    GREATEST(1, CEIL(TIMESTAMPDIFF(SECOND, c.due_at, NOW()) / 86400)) AS days_late
             FROM checkouts c
             JOIN reservations r ON r.reservation_id = c.reservation_id
             JOIN serialized_items si ON si.item_id = c.item_id
             JOIN equipment_catalog ec ON ec.catalog_id = si.catalog_id
             WHERE r.borrower_id = :uid AND c.returned_at IS NULL AND NOW() > c.due_at
             ORDER BY c.due_at ASC"
        );
        $stm->execute([':uid' => $borrowerId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Returned loans a staff member can charge a fine on (a loan may carry several fines). */
    public function listChargeableCheckouts(): array
    {
        $stm = $this->db->query(
            "SELECT c.checkout_id, c.reservation_id, c.returned_at,
                    si.serial_number, ec.model_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS borrower_name
             FROM checkouts c
             JOIN reservations r ON r.reservation_id = c.reservation_id
             JOIN serialized_items si ON si.item_id = c.item_id
             JOIN equipment_catalog ec ON ec.catalog_id = si.catalog_id
             JOIN users u ON u.user_id = r.borrower_id
             WHERE c.returned_at IS NOT NULL
             ORDER BY c.checkout_id DESC
             LIMIT 200"
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Summary strip numbers for a list of fines (from listAllDetailed / listForBorrower) and the
     * still-accruing late fines (from listAccruing). Pure arithmetic, so pages do not have to repeat it.
     */
    public static function totals(array $fines, array $accruing = []): array
    {
        $t = ['unpaidTotal' => 0.0, 'unpaidCount' => 0, 'paidTotal' => 0.0, 'paidCount' => 0, 'waivedCount' => 0, 'accruingTotal' => 0.0];

        foreach ($accruing as $a) {
            $t['accruingTotal'] += (float) $a['late_fine_rate'] * (int) $a['days_late'];
        }
        foreach ($fines as $f) {
            if ($f['payment_status'] === 'unpaid') {
                $t['unpaidTotal'] += (float) $f['amount_due'];
                $t['unpaidCount']++;
            } elseif ($f['payment_status'] === 'paid') {
                $t['paidTotal'] += (float) $f['amount_due'];
                $t['paidCount']++;
            } else {
                $t['waivedCount']++;
            }
        }
        return $t;
    }

    private function lockFine(int $fineId): ?array
    {
        $stm = $this->db->prepare(
            'SELECT f.fine_id, f.amount_due, f.payment_status, c.reservation_id, c.item_id
             FROM fines f
             JOIN checkouts c ON c.checkout_id = f.checkout_id
             WHERE f.fine_id = :fine_id
             FOR UPDATE'
        );
        $stm->execute([':fine_id' => $fineId]);
        return $stm->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
