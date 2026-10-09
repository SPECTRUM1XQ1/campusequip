<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';

class TransactionLog
{
    private const ACTION_TYPES = [
        'requested', 'approved', 'declined', 'cancelled',
        'handover', 'returned', 'returned_late',
        'fine_charged', 'fine_paid', 'fine_waived',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function record(
        ?int $reservationId,
        ?int $itemId,
        ?int $fineId,
        int $actorId,
        string $actionType,
        ?string $remarks = null,
        ?string $serialSnapshot = null
    ): bool {
        if (!in_array($actionType, self::ACTION_TYPES, true)) {
            throw new InvalidArgumentException("Unknown action type: {$actionType}");
        }

        if ($serialSnapshot === null && $itemId !== null) {
            $lookup = $this->db->prepare('SELECT serial_number FROM serialized_items WHERE item_id = :item_id');
            $lookup->execute([':item_id' => $itemId]);
            $serialSnapshot = $lookup->fetchColumn() ?: null;
        }

        $stm = $this->db->prepare(
            'INSERT INTO transaction_logs
                (reservation_id, item_id, fine_id, actor_id, action_type, remarks, serial_snapshot, action_timestamp)
             VALUES
                (:reservation_id, :item_id, :fine_id, :actor_id, :action_type, :remarks, :serial_snapshot, NOW())'
        );

        return $stm->execute([
            ':reservation_id' => $reservationId,
            ':item_id'        => $itemId,
            ':fine_id'        => $fineId,
            ':actor_id'       => $actorId,
            ':action_type'    => $actionType,
            ':remarks'        => $remarks,
            ':serial_snapshot'=> $serialSnapshot,
        ]);
    }
}
