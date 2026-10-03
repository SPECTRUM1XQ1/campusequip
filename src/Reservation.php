<?php 
if (!defined('APP_INT')) {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/Database.php'; 
class Reservation{
    private PDO $db;
    public function __construct() {
        $this->db = Database::getConnection();
    }
    function create(int $borrowerId, int $catalogId, string $startAt, string $endAt, string $purpose) : array {
        if ($endAt <= $startAt) {
            return ['success' => false,'error'=> 'End Date Must Be After the Start Date'];   
        }
        $stm = $this->db->prepare('INSERT INTO reservations (catalog_id, borrower_id, status, purpose, start_at, end_at, terms_accepted)
        VALUES(:catalog_id, :borrower_id, :status, :purpose, :start_at, :end_at, 1)');
        $stm->execute([
            ':catalog_id'    => $catalogId, 
            ':borrower_id'   => $borrowerId, 
            ':status'        => 'pending', 
            ':purpose'       => $purpose, 
            ':start_at'      => $startAt, 
            ':end_at'        => $endAt, 
        ]);
        $reservationId = (int) $this->db->lastInsertId();
        $this->log($reservationId, null, $borrowerId, 'requested');

        return ['success'=> true,'reservation_id'=> $reservationId];
    }
    function listForBorrower(int $borrowerId) : array {
        $stm = $this->db->prepare(
            'SELECT r.*, ec.model 
            FROM reservations r 
            JOIN equipment_catalog ec ON ec.catalog_id = r.catalog_id 
            WHERE r.borrower = :borrower_id 
            ORDER BY r.requested_at DESC'
        );
        $stm->execute([':borrower_id' => $borrowerId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    function listPending(): array {
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
    function countPending() : int {
        return (int) $this->db->query(" SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
    }
    function hasDataConflict(int $itemId,string $startAt,string $endAt) : bool {
        $stm = $this->db->prepare(
            "SELECT reservation_id FROM reservations WHERE item_id = :item_id
            AND status IN ('approved', 'checked_out')
            AND start_at < :end_at
            AND end_at > :start_at"
        );
        $stm->execute([
            ":item_id"=> $itemId,
            ":start_at"=> $startAt,
            ":end_at"=> $endAt,
        ]);
        return (bool) $stm->fetch(PDO::FETCH_ASSOC);
    }
    function approve(int $reservationId, int $itemId, int $reviewerId): array {
        $stm = $this->db->prepare('SELECT * FROM reservations WHERE reservation_id = :id');
        $stm->execute([':id' => $reservationId]);
        $reservation = $stm->fetch(PDO::FETCH_ASSOC);
        if (!$reservation) {
            return ['success' => false,'message'=> 'Reservation not Found. '];
        }

        $stm = $this->db->prepare('SELECT catalog_id FROM serialized_items WHERE item_id = :id');
        $stm ->execute([':id' => $itemId]);
        $item = $stm->fetch(PDO::FETCH_ASSOC);
        if (!$item || (int)$item['catalog_id'] !== (int)$reservation['catalog_id']) {
            return ['success' => false, 'message' => 'Invalid unit selection for this reservation catalog.'];
        }

        if ($this->hasDataConflict($itemId, $reservation['start_at'], $reservation['end_at'])) {
            return ['success'=> false,'message'=> 'That Unit is Already booked for an overlapping date range.'];
        }

        $stm = $this->db->prepare(
            "UPDATE reservations 
            SET status = 'approved', item_id = :item_id, reviewed_by = :reviewed_by, reviewed_at  = NOW() 
            WHERE reservation_id = :id");
        $stm->execute([
            ':item_id' => $itemId,
            ':reviewed_by' => $reviewerId,
            ':id' => $reservationId 
        ]);
        //Unit is Reserved
        $this->db->prepare("UPDATE serialized_items SET status = 'reserved' WHERE item_id = :id")->execute([':id' => $itemId]);
        $this->log($reservationId, $itemId, $reviewerId, 'approved');
        return ['success'=> true];
    }
    function decline(int $reservationId, int $reviewerId, string $reason): array {
        $stm = $this->db->prepare(
            "UPDATE reservations
            SET status = 'declined', decline_reason = :reason, reviewed_by = :reviewed_id, reviewed_at = NOW()
            WHERE reservation_id = :id AND status = 'pending'"
        );

        $stm->execute([
            ':reason' => $reason, 
            ':reviewed_id'=> $reviewerId, 
            ':id'=> $reservationId
        ]);

        if ($stm->rowCount() === 0) {
            return ['success'=> false,'message'=> 'Reservation not Found or Already Process'];
        }
        $this->log($reservationId, null, $reviewerId, 'declined', $reason);
        return ['success'=> true];
    }
    public function cancel(int $reservationId, int $borrowerId): array {
        $stm = $this->db->prepare(
            'SELECT item_id, status FROM reservations 
            WHERE reservation_id = :id AND borrower_id = :borrower_id');
        $stm->execute([
            ':id' => $reservationId, 
            ':borrower_id' => $borrowerId]);
        $reservation = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$reservation || !in_array($reservation['status'], ['pending', 'approved'], true)) {
            return ['success' => false, 'message' => 'Reservation cannot be cancelled.'];
        }

        $stm = $this->db->prepare(
            "UPDATE reservations 
             SET status = 'cancelled'
             WHERE reservation_id = :id"
        );
        $stm->execute([':id' => $reservationId]);

        if ($reservation['status'] === 'approved' && !empty($reservation['item_id'])) {
            $this->db->prepare("UPDATE serialized_items SET status = 'available' WHERE item_id = :id")
                     ->execute([':id' => $reservation['item_id']]);
        }

        $this->log($reservationId, $reservation['item_id'], $borrowerId, 'cancelled');
        return ['success' => true];
    }
    private function log(int $reservationId, ?int $itemId, int $actorId, string $actionType, ?string $remark = null): void {
        $stm = $this->db->prepare(
            "INSERT INTO transaction_logs(reservation_id, item_id, actor_id, action_type, remarks)
            VALUES(:reservation_id, :item_id, :actor_id, :action_type, :remark)");
        $stm->execute([
        ':reservation_id' => $reservationId,
        ':item_id' => $itemId, 
        ':actor_id' => $actorId, 
        ':action_type'=> $actionType, 
        ':remark'=> $remark
        ]);
    }
}
?>