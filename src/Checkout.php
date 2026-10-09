<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/TransactionLog.php';

class Checkout
{
    private PDO $db;
    private TransactionLog $logger;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->logger = new TransactionLog();
    }

    public function handover(int $reservationId, int $issuedBy, bool $idVerified): array
    {
        if (!$idVerified) {
            return ['success' => false, 'message' => 'Student ID verification is required before issuing the equipment.'];
        }

        try {
            $this->db->beginTransaction();

            $stm = $this->db->prepare('SELECT * FROM reservations WHERE reservation_id = :id FOR UPDATE');
            $stm->execute([':id' => $reservationId]);
            $reservation = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Reservation not found.'];
            }
            if ($reservation['status'] !== 'approved') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Reservation is not approved for checkout.'];
            }
            if (empty($reservation['item_id'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'No specific serial item is assigned to this reservation.'];
            }

            $itemId = (int) $reservation['item_id'];

            $stm = $this->db->prepare('SELECT status FROM serialized_items WHERE item_id = :id FOR UPDATE');
            $stm->execute([':id' => $itemId]);
            $unitStatus = $stm->fetchColumn();

            if ($unitStatus === false || in_array($unitStatus, ['maintenance', 'retired'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'This unit is under maintenance or retired and cannot be checked out.'];
            }

            $stm = $this->db->prepare('SELECT checkout_id FROM checkouts WHERE item_id = :id AND returned_at IS NULL LIMIT 1');
            $stm->execute([':id' => $itemId]);
            if ($stm->fetch()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'This unit has not been returned by its previous borrower yet.'];
            }

            $stm = $this->db->prepare(
                'INSERT INTO checkouts (reservation_id, item_id, issued_by, id_verified, checked_out_at, due_at)
                 VALUES (:reservation_id, :item_id, :issued_by, :id_verified, NOW(), :due_at)'
            );
            $stm->execute([
                ':reservation_id' => $reservationId,
                ':item_id'        => $itemId,
                ':issued_by'      => $issuedBy,
                ':id_verified'    => 1,
                ':due_at'         => $reservation['end_at'],
            ]);
            $checkoutId = (int) $this->db->lastInsertId();

            $stm = $this->db->prepare("UPDATE reservations SET status = 'checked_out', pickup_at = NOW() WHERE reservation_id = :id");
            $stm->execute([':id' => $reservationId]);

            $stm = $this->db->prepare("UPDATE serialized_items SET status = 'checked_out' WHERE item_id = :id");
            $stm->execute([':id' => $itemId]);

            $this->logger->record($reservationId, $itemId, null, $issuedBy, 'handover', 'Equipment handed over to borrower');

            $this->db->commit();
            return [
                'success'     => true,
                'checkout_id' => $checkoutId,
                'message'     => 'Equipment handed over successfully.',
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Checkout::handover failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Handover failed. Please try again.'];
        }
    }
    public function processReturn(
        int $checkoutId,
        int $receivedBy,
        string $returnCondition,
        ?string $damageNotes = null,
        ?string $inspectionRemarks = null
    ): array {
        if (!in_array($returnCondition, ['good', 'damaged', 'missing_accessories'], true)) {
            return ['success' => false, 'message' => 'Invalid return condition.'];
        }

        try {
            $this->db->beginTransaction();

            $stm = $this->db->prepare(
                'SELECT reservation_id, item_id, returned_at, (NOW() > due_at) AS is_late
                 FROM checkouts
                 WHERE checkout_id = :checkout_id
                 FOR UPDATE'
            );
            $stm->execute([':checkout_id' => $checkoutId]);
            $checkout = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$checkout) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Checkout record not found.'];
            }
            if ($checkout['returned_at'] !== null) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'This equipment has already been returned.'];
            }

            $stm = $this->db->prepare(
                'UPDATE checkouts
                 SET returned_at = NOW(), received_by = :received_by, return_condition = :return_condition,
                     damage_notes = :damage_notes, inspection_remarks = :inspection_remarks
                 WHERE checkout_id = :checkout_id'
            );
            $stm->execute([
                ':checkout_id'        => $checkoutId,
                ':received_by'        => $receivedBy,
                ':return_condition'   => $returnCondition,
                ':damage_notes'       => $damageNotes,
                ':inspection_remarks' => $inspectionRemarks,
            ]);

            $stm = $this->db->prepare("UPDATE reservations SET status = 'returned' WHERE reservation_id = :reservation_id");
            $stm->execute([':reservation_id' => $checkout['reservation_id']]);

            // A clean return goes back on the shelf; anything else needs a look first.
            $newUnitStatus = ($returnCondition === 'good') ? 'available' : 'maintenance';
            $stm = $this->db->prepare('UPDATE serialized_items SET status = :status WHERE item_id = :item_id');
            $stm->execute([':status' => $newUnitStatus, ':item_id' => $checkout['item_id']]);

            $isLate = ((int) $checkout['is_late'] === 1);
            $this->logger->record(
                (int) $checkout['reservation_id'],
                (int) $checkout['item_id'],
                null,
                $receivedBy,
                $isLate ? 'returned_late' : 'returned',
                $inspectionRemarks
            );

            $this->db->commit();
            return [
                'success'     => true,
                'checkout_id' => $checkoutId,
                'is_late'     => $isLate,
                'message'     => 'Equipment returned successfully.',
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Checkout::processReturn failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Return failed. Please try again.'];
        }
    }

    public function getByReservation(int $reservationId): ?array
    {
        $stm = $this->db->prepare(
            'SELECT c.*, si.serial_number, si.storage_location, si.condition_notes
             FROM checkouts c
             LEFT JOIN serialized_items si ON si.item_id = c.item_id
             WHERE c.reservation_id = :reservation_id'
        );
        $stm->execute([':reservation_id' => $reservationId]);
        return $stm->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listActive(): array
    {
        $stm = $this->db->query(
            'SELECT c.*, r.borrower_id, si.serial_number
             FROM checkouts c
             LEFT JOIN reservations r ON r.reservation_id = c.reservation_id
             LEFT JOIN serialized_items si ON si.item_id = c.item_id
             WHERE c.returned_at IS NULL
             ORDER BY c.checked_out_at DESC'
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listOverdue(): array
    {
        $stm = $this->db->query(
            'SELECT c.*, r.borrower_id, si.serial_number
             FROM checkouts c
             LEFT JOIN reservations r ON r.reservation_id = c.reservation_id
             LEFT JOIN serialized_items si ON si.item_id = c.item_id
             WHERE c.returned_at IS NULL AND c.due_at < NOW()
             ORDER BY c.due_at ASC'
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
}
