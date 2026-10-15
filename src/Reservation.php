<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/TransactionLog.php';

class Reservation
{
    private PDO $db;
    private TransactionLog $logger;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->logger = new TransactionLog();
    }
    public function create(int $borrowerId, int $catalogId, string $startAt, string $endAt, string $purpose): array
    {
        $start = strtotime($startAt);
        $end   = strtotime($endAt);

        if ($start === false || $end === false) {
            return ['success' => false, 'message' => 'Invalid date format.'];
        }
        if ($end <= $start) {
            return ['success' => false, 'message' => 'End date must be after the start date.'];
        }

        try {
            $stm = $this->db->prepare(
                'SELECT max_loan_days, is_archived, (DATE(:start_at) < CURDATE()) AS start_in_past
                 FROM equipment_catalog
                 WHERE catalog_id = :catalog_id'
            );
            $stm->execute([':start_at' => $startAt, ':catalog_id' => $catalogId]);
            $catalog = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$catalog) {
                return ['success' => false, 'message' => 'Equipment model not found.'];
            }
            if ((int) $catalog['is_archived'] === 1) {
                return ['success' => false, 'message' => 'This equipment is no longer available for reservation.'];
            }
            if ((int) $catalog['start_in_past'] === 1) {
                return ['success' => false, 'message' => 'The start date cannot be in the past.'];
            }

            $days = ($end - $start) / 86400;
            if ($days > (int) $catalog['max_loan_days']) {
                return ['success' => false, 'message' => 'This equipment can only be borrowed for up to ' . (int) $catalog['max_loan_days'] . ' day(s).'];
            }

            $this->db->beginTransaction();

            $stm = $this->db->prepare(
                "INSERT INTO reservations (catalog_id, borrower_id, status, purpose, start_at, end_at, terms_accepted)
                 VALUES (:catalog_id, :borrower_id, 'pending', :purpose, :start_at, :end_at, 1)"
            );
            $stm->execute([
                ':catalog_id'  => $catalogId,
                ':borrower_id' => $borrowerId,
                ':purpose'     => $purpose,
                ':start_at'    => $startAt,
                ':end_at'      => $endAt,
            ]);

            $reservationId = (int) $this->db->lastInsertId();

            $this->logger->record($reservationId, null, null, $borrowerId, 'requested', $purpose);

            $this->db->commit();
            return ['success' => true, 'reservation_id' => $reservationId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reservation::create failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not create the reservation. Please try again.'];
        }
    }
    public function listForBorrower(int $borrowerId): array
    {
        $stm = $this->db->prepare(
            'SELECT r.*, ec.model_name
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             WHERE r.borrower_id = :borrower_id
             ORDER BY r.requested_at DESC'
        );
        $stm->execute([':borrower_id' => $borrowerId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    public function listPending(): array
    {
        $stm = $this->db->query(
            "SELECT r.*, ec.model_name, u.first_name, u.last_name
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             JOIN users u ON u.user_id = r.borrower_id
             WHERE r.status = 'pending'
             ORDER BY r.requested_at ASC"
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    // In src/Reservation.php

/**
 * Returns pending reservations with borrower details and available serialized units for immediate assignment.
 */
    public function listPendingDetailed(): array
    {
        $stm = $this->db->query(
            "SELECT r.reservation_id, r.catalog_id, r.borrower_id, r.status, r.purpose,
                    r.start_at, r.end_at, r.requested_at,
                    ec.model_name, ec.max_loan_days,
                    CONCAT(u.first_name, ' ', COALESCE(CONCAT(u.middle_name, ' '), ''), u.last_name) AS borrower_name,
                    u.email AS borrower_email, u.phone AS borrower_phone, u.id_number AS borrower_id_number
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             JOIN users u ON u.user_id = r.borrower_id
             WHERE r.status = 'pending'
             ORDER BY r.requested_at ASC"
        );
        $requests = $stm->fetchAll(PDO::FETCH_ASSOC);
    
        // Attach available serialized units for each catalog model
        $unitStmt = $this->db->prepare(
            "SELECT item_id, serial_number, storage_location 
             FROM serialized_items 
             WHERE catalog_id = :catalog_id AND status = 'available'
             ORDER BY serial_number ASC"
        );
    
        foreach ($requests as &$req) {
            $unitStmt->execute([':catalog_id' => $req['catalog_id']]);
            $req['available_units'] = $unitStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    
        return $requests;
    }
    
    /**
     * Returns approved reservations awaiting staff handover.
     */
    public function listReadyForCheckout(): array
    {
        $stm = $this->db->query(
            "SELECT r.reservation_id, r.item_id, r.start_at, r.end_at, r.reviewed_at,
                    ec.model_name, si.serial_number, si.storage_location,
                    CONCAT(u.first_name, ' ', u.last_name) AS borrower_name,
                    u.id_number AS borrower_id_number,
                    CONCAT(staff.first_name, ' ', staff.last_name) AS assigned_by_name
             FROM reservations r
             JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id
             JOIN serialized_items si ON si.item_id = r.item_id
             JOIN users u ON u.user_id = r.borrower_id
             LEFT JOIN users staff ON staff.user_id = r.reviewed_by
             WHERE r.status = 'approved'
             ORDER BY r.start_at ASC"
        );
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPending(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
    }
    public function hasDateConflict(int $itemId, string $startAt, string $endAt): bool
    {
        $stm = $this->db->prepare(
            "SELECT reservation_id FROM reservations
             WHERE item_id = :item_id
               AND status IN ('approved', 'checked_out')
               AND start_at < :end_at
               AND end_at > :start_at"
        );
        $stm->execute([
            ':item_id'  => $itemId,
            ':start_at' => $startAt,
            ':end_at'   => $endAt,
        ]);
        return (bool) $stm->fetch(PDO::FETCH_ASSOC);
    }

    public function approve(int $reservationId, int $itemId, int $reviewerId): array
    {
        try {
            $this->db->beginTransaction();

            // Lock the reservation so two staff can't approve it at once.
            $stm = $this->db->prepare('SELECT * FROM reservations WHERE reservation_id = :id FOR UPDATE');
            $stm->execute([':id' => $reservationId]);
            $reservation = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Reservation not found.'];
            }
            if ($reservation['status'] !== 'pending') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Reservation is not in pending status.'];
            }

            $stm = $this->db->prepare('SELECT catalog_id, serial_number, status FROM serialized_items WHERE item_id = :id FOR UPDATE');
            $stm->execute([':id' => $itemId]);
            $item = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$item || (int) $item['catalog_id'] !== (int) $reservation['catalog_id']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Invalid unit selection for this reservation.'];
            }
            if (in_array($item['status'], ['maintenance', 'retired'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'That unit is under maintenance or retired and cannot be assigned.'];
            }

            if ($this->hasDateConflict($itemId, $reservation['start_at'], $reservation['end_at'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'That unit is already booked for an overlapping date range.'];
            }

            $stm = $this->db->prepare(
                "UPDATE reservations
                 SET status = 'approved', item_id = :item_id, reviewed_by = :reviewed_by, reviewed_at = NOW()
                 WHERE reservation_id = :id"
            );
            $stm->execute([
                ':item_id'     => $itemId,
                ':reviewed_by' => $reviewerId,
                ':id'          => $reservationId,
            ]);

            $this->logger->record($reservationId, $itemId, null, $reviewerId, 'approved', null, $item['serial_number']);

            $this->db->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reservation::approve failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not approve the reservation. Please try again.'];
        }
    }
    public function decline(int $reservationId, int $reviewerId, string $reason): array
    {
        try {
            $this->db->beginTransaction();

            $stm = $this->db->prepare(
                "UPDATE reservations
                 SET status = 'declined', decline_reason = :reason, reviewed_by = :reviewed_by, reviewed_at = NOW()
                 WHERE reservation_id = :id AND status = 'pending'"
            );
            $stm->execute([
                ':reason'      => $reason,
                ':reviewed_by' => $reviewerId,
                ':id'          => $reservationId,
            ]);

            if ($stm->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Reservation not found or already processed.'];
            }

            $this->logger->record($reservationId, null, null, $reviewerId, 'declined', $reason);

            $this->db->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reservation::decline failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not decline the reservation. Please try again.'];
        }
    }
    public function cancel(int $reservationId, int $borrowerId): array
    {
        try {
            $this->db->beginTransaction();

            $stm = $this->db->prepare(
                'SELECT item_id, status FROM reservations
                 WHERE reservation_id = :id AND borrower_id = :borrower_id
                 FOR UPDATE'
            );
            $stm->execute([
                ':id'          => $reservationId,
                ':borrower_id' => $borrowerId,
            ]);
            $reservation = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$reservation || !in_array($reservation['status'], ['pending', 'approved'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Reservation cannot be cancelled.'];
            }

            $stm = $this->db->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = :id");
            $stm->execute([':id' => $reservationId]);

            $itemId = $reservation['item_id'] !== null ? (int) $reservation['item_id'] : null;
            $this->logger->record($reservationId, $itemId, null, $borrowerId, 'cancelled');

            $this->db->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reservation::cancel failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not cancel the reservation. Please try again.'];
        }
    }
}