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

    public function charge(int $checkoutId, int $staffId, float $amount): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'The amount must be greater than 0.'];
        }

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

            $stm = $this->db->prepare("SELECT fine_id FROM fines WHERE checkout_id = :checkout_id AND payment_status = 'unpaid' LIMIT 1");
            $stm->execute([':checkout_id' => $checkoutId]);
            if ($stm->fetch()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'This checkout already has an unpaid fine. Settle or waive it first.'];
            }

            $stm = $this->db->prepare(
                "INSERT INTO fines (checkout_id, amount_due, payment_status)
                 VALUES (:checkout_id, :amount_due, 'unpaid')"
            );
            $stm->execute([
                ':checkout_id' => $checkoutId,
                ':amount_due'  => $amount,
            ]);
            $fineId = (int) $this->db->lastInsertId();

            $this->logger->record(
                (int) $checkout['reservation_id'],
                (int) $checkout['item_id'],
                $fineId,
                $staffId,
                'fine_charged',
                'Fine charged: ' . number_format($amount, 2)
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
