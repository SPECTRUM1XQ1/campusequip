<?php 
if (!defined('APP_INIT')) { http_response_code(403); exit; }

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ .'/../src/TransactionLog.php';
class Checkout
{
    private PDO $db;
    private TransactionLog $transactionLog;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->transactionLog = new TransactionLog();
    }

    public function handover(int $reservationId, int $issuedBy, bool $idVerified): array {
        $resstm = $this->db->prepare("SELECT * FROM reservations WHERE reservation_id = :reservation_id");
        $resstm->execute([':reservation_id' => $reservationId]);              
        $reservation = $resstm->fetch(PDO::FETCH_ASSOC);

        if ($idVerified === false) {
            return['success' => false, 'message' => 'Student ID verification is required BEFORE Issuing the equipment'];
        }

        if (!$reservation) {
            return['success'=> false, 'message'=> 'Reservation Not Found'];
        }

        if ($reservation['status'] !== 'approved') {
            return['success'=> false, 'message'=> 'Reservation is NOT Approved for Checkout'];
        }

        if (empty($reservation['item_id'])) {
            return['success'=> false, 'message'=> 'NO specific serial item assigned to this reservation'];
        
        }

        try {
            $this->db->beginTransaction();  
            $stm = $this->db->prepare(
                "INSERT INTO checkouts (
                    reservation_id, 
                    item_id, issued_by, 
                    id_verified, 
                    checked_out_at, 
                    due_at)
                VALUES (:reservation_id, :item_id, :issued_by, :id_verified, NOW(), :due_at)"
            );
            $stm->execute([
                ':reservation_id'=> $reservationId,
                ':item_id'=> $reservation['item_id'],
                ':issued_by'=> $issuedBy,
                ':id_verified' => $idVerified ? 1:0,
                ':due_at' => $reservation['end_at'],
            ]);
            $checkoutId = (int) $this->db->lastInsertId();

            $resUpdateStm = $this->db->prepare("UPDATE reservations SET status = 'checked_out', pickup_at = NOW() WHERE reservation_id = :id");
            $resUpdateStm->execute([':id' => $reservationId]);

            $itemUpdateStm = $this->db->prepare("UPDATE serialized_items SET status = 'checked_out' WHERE item_id = :id");
            $itemUpdateStm->execute([':id' => $reservation['item_id']]);

            if (isset($this->transactionLog) && method_exists($this->transactionLog, 'record')) {
                $this->transactionLog->record($reservationId, $reservation['item_id'], $issuedBy, 'check_out', 'Equipment Hand Over to Borrower');    
            }
            $this->db->commit();
            return[
                'success' => true,
                'checkout_id' => $checkoutId,
                'message' => 'Equipment handed over successfully'
                ];
        } catch (\Throwable $th) {
            if($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Handover failed: ' . $th->getMessage()];
        }
    }
    public function processReturn(int $checkoutId, int $receivedBy, string $returnCondition, ?string $damageNotes = null, ?string $inspectionRemarks = null): array {
        $stm = $this->db->prepare("SELECT reservation_id, item_id, due_at ,returned_at FROM checkouts WHERE checkout_id = :checkout_id");
        $stm->execute([':checkout_id' => $checkoutId]);
        $checkout = $stm->fetch(PDO::FETCH_ASSOC);
    
        if (!$checkout) {
            return ['success'=> false,'message'=> 'Checkout Record Not Found'];
        }

        if ($checkout['returned_at'] !== null){
            return['success' => false, 'message' => 'Equipment has Already Been Returned'];
        }

        if (!in_array($returnCondition, ['good','damaged','missing_accessories'] ,true)) {
            return ['success'=> false, 'message' => 'Invalid Condition'];
        }

        try {
            $this->db->beginTransaction();

            $checkUpdateStm = $this->db->prepare(
                "UPDATE checkouts
                SET returned_at = NOW(), received_by = :received_by, return_condition = :return_condition, damage_notes = :damage_notes, inspection_remarks = :inspection_remarks
                WHERE checkout_id = :checkout_id"
            );

            $checkUpdateStm->execute([
                ":checkout_id"=> $checkoutId,
                ':received_by' => $receivedBy,
                ':return_condition' => $returnCondition,
                ':damage_notes' => $damageNotes,
                ':inspection_remarks' => $inspectionRemarks
            ]);

            $resUpdateStm = $this->db->prepare('UPDATE reservations SET status = :status WHERE reservation_id = :reservation_id');
            $resUpdateStm->execute(['reservation_id' => $checkout['reservation_id'], ':status' => 'returned']);

            if ($returnCondition === 'good') {
                $itemUpdateStm = $this->db->prepare("UPDATE serialized_items SET status = 'available' WHERE item_id = :item_id" );
                $itemUpdateStm->execute([':item_id' => $checkout['item_id']]);
            }

            if ($returnCondition === 'damaged' || $returnCondition === 'missing_accessories') {
                $itemUpdateStm = $this->db->prepare("UPDATE serialized_items SET status = 'maintenance' WHERE item_id = :item_id");
                $itemUpdateStm->execute([':item_id' => $checkout['item_id']]);
            }

            date_default_timezone_set('Asia/Manila');
            if(date('Y-m-d H:i:s') > $checkout['due_at']){
                $lateReturnStm = $this->db->prepare("UPDATE transaction_logs SET action_type = 'returned_late' WHERE reservation_id = :reservation_id  ");
                $lateReturnStm->execute([':reservation_id' => $checkout['reservation_id']]);
            }

            $this->transactionLog->record($checkoutId, $checkout['item_id'] ,$receivedBy, 'returned', $inspectionRemarks);
            $this->db->commit();
            return[
                'success' => true,
                'checkout_id' => $checkoutId,
                'message' => 'Equipment Returned successfully'
            ];
        } catch (\Throwable $th) {
            if($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Return failed: ' . $th->getMessage()];
        }
    }
    public function getByReservation(int $reservationId): ?array {}
    public function listActive(): array {
        $stm = $this->db->query(
            "SELECT COUNT(status) 
             FROM reservations 
             WHERE status = 'checked_out' 
             GROUP BY status");
        $stm->execute();
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    public function listOverdue(): array {
        $stm = $this->db->query(
            "SELECT 
                c.checkout_id, 
                c.reservation_id, 
                c.item_id, 
                c.issued_by 
                si.serial_number
                u.user_id
                u.last_name
                u.first_name
                u.middle_name 
            FROM checkouts c 
            LEFT JOIN serialized_items si ON c.checkout_id = si.serial_number
            LEFT JOIN users u ON u.user_id = c.issued_by
            WHERE due_at > checked_out_at");   
        $stm->execute();   
        $stm->fetchAll(PDO::FETCH_ASSOC);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 