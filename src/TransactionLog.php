<?php 
if (!defined('APP_INIT')) { http_response_code(403); exit; }

require_once __DIR__ . '/../config/Database.php';
class TransactionLog
{
    private PDO $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function record(?int $reservationId, ?int $itemId, int $actorId, string $actionType, ?string $remarks = null): void {
        $stm = $this->db->prepare(
            "INSERT INTO transaction_logs(reservation_id, item_id, fine_id, actor_id, action_type, remarks, serial_snapshot, action_timestamp)
            VALUES(:reservation_id, 
            :item_id, 
            :fine_id, 
            :actor_id, 
            :action_type, 
            :remarks, 
            :serial_snapshot, 
            :action_timestamp
        )");
        $stm->execute([
            ':reservation_id' => $ $reservationId,
            ':item_id' => $itemId,
            ':fine_id' => $itemId ?? null,
            ':actor_id' => $actorId ,
            ':action_type' => $ $actionType,
            ':remarks' => $ $remarks ?? null,
            ':serial_snapshot' => $ $itemId,
            ':action_timestamp' => 'NOW()'
        ]);
    }
}
?>