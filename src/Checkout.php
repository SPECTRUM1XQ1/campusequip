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
        ?string $inspectionRemarks = null,
        array $missingAccessoryIds = []
    ): array {
        if (!in_array($returnCondition, ['good', 'damaged', 'missing_accessories'], true)) {
            return ['success' => false, 'message' => 'Invalid return condition.'];
        }

        // Each condition has its own required detail.
        $damageNotes = ($damageNotes !== null && trim($damageNotes) !== '') ? trim($damageNotes) : null;
        if ($returnCondition === 'damaged' && $damageNotes === null) {
            return ['success' => false, 'message' => 'Please describe what happened to the item.'];
        }

        $missingAccessoryIds = array_values(array_unique(array_filter(array_map('intval', $missingAccessoryIds), fn($id) => $id > 0)));
        if ($returnCondition === 'missing_accessories' && empty($missingAccessoryIds)) {
            return ['success' => false, 'message' => 'Select at least one missing accessory.'];
        }
        if ($returnCondition !== 'damaged') {
            $damageNotes = null; // only meaningful for damaged items
        }
        if ($returnCondition !== 'missing_accessories') {
            $missingAccessoryIds = [];
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

            // Only accessories that belong to THIS unit's model may be reported missing.
            $missingNames = [];
            if (!empty($missingAccessoryIds)) {
                $in = implode(',', array_fill(0, count($missingAccessoryIds), '?'));
                $stm = $this->db->prepare(
                    "SELECT a.accessory_id, a.accessory_name
                     FROM accessories a
                     JOIN serialized_items si ON si.catalog_id = a.catalog_id
                     WHERE si.item_id = ? AND a.accessory_id IN ($in)"
                );
                $stm->execute(array_merge([(int) $checkout['item_id']], $missingAccessoryIds));
                $valid = $stm->fetchAll(PDO::FETCH_ASSOC);

                if (count($valid) !== count($missingAccessoryIds)) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'One or more selected accessories do not belong to this item.'];
                }
                $missingNames = array_column($valid, 'accessory_name');
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

            if (!empty($missingAccessoryIds)) {
                $stm = $this->db->prepare(
                    'INSERT INTO checkout_missing_accessories (checkout_id, accessory_id) VALUES (:checkout_id, :accessory_id)'
                );
                foreach ($missingAccessoryIds as $accessoryId) {
                    $stm->execute([':checkout_id' => $checkoutId, ':accessory_id' => $accessoryId]);
                }
            }

            $stm = $this->db->prepare("UPDATE reservations SET status = 'returned' WHERE reservation_id = :reservation_id");
            $stm->execute([':reservation_id' => $checkout['reservation_id']]);

            // A clean return goes back on the shelf; anything else needs a look first.
            $newUnitStatus = ($returnCondition === 'good') ? 'available' : 'maintenance';
            $stm = $this->db->prepare('UPDATE serialized_items SET status = :status WHERE item_id = :item_id');
            $stm->execute([':status' => $newUnitStatus, ':item_id' => $checkout['item_id']]);

            $isLate = ((int) $checkout['is_late'] === 1);

            $remarkParts = [];
            if ($returnCondition === 'damaged') {
                $remarkParts[] = 'Damaged: ' . $damageNotes;
            } elseif ($returnCondition === 'missing_accessories') {
                $remarkParts[] = 'Missing accessories: ' . implode(', ', $missingNames);
            }
            if ($inspectionRemarks !== null && trim($inspectionRemarks) !== '') {
                $remarkParts[] = trim($inspectionRemarks);
            }

            $this->logger->record(
                (int) $checkout['reservation_id'],
                (int) $checkout['item_id'],
                null,
                $receivedBy,
                $isLate ? 'returned_late' : 'returned',
                $remarkParts ? implode(' | ', $remarkParts) : null
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

    /** Accessories reported missing when this checkout was returned. */
    public function getMissingAccessories(int $checkoutId): array
    {
        $stm = $this->db->prepare(
            'SELECT a.accessory_id, a.accessory_name
             FROM checkout_missing_accessories cma
             JOIN accessories a ON a.accessory_id = cma.accessory_id
             WHERE cma.checkout_id = :checkout_id
             ORDER BY a.accessory_name ASC'
        );
        $stm->execute([':checkout_id' => $checkoutId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
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

    // In src/Checkout.php

    /**
     * Detailed active checkouts for return processing.
     */
    public function listActiveDetailed(): array
    {
        $stm = $this->db->query(
            "SELECT c.checkout_id, c.reservation_id, c.item_id, si.catalog_id, c.checked_out_at, c.due_at,
                    (NOW() > c.due_at) AS is_overdue,
                    TIMESTAMPDIFF(DAY, c.due_at, NOW()) AS overdue_days,
                    si.serial_number, ec.model_name, ec.late_fine_rate,
                    CONCAT(u.first_name, ' ', u.last_name) AS borrower_name,
                    u.id_number AS borrower_id_number,
                    CONCAT(staff.first_name, ' ', staff.last_name) AS issued_by_name
             FROM checkouts c
             JOIN reservations r ON r.reservation_id = c.reservation_id
             JOIN serialized_items si ON si.item_id = c.item_id
             JOIN equipment_catalog ec ON ec.catalog_id = si.catalog_id
             JOIN users u ON u.user_id = r.borrower_id
             LEFT JOIN users staff ON staff.user_id = c.issued_by
             WHERE c.returned_at IS NULL
             ORDER BY c.due_at ASC"
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
}
