<?php 
class TransactionLog
{
    private PDO $db;
    public function __construct() { $this->db = Database::getConnection(); }

    public function record(?int $reservationId, ?int $itemId, int $actorId, string $actionType, ?string $remarks = null): void
    {
        // the same INSERT currently sitting in Reservation's private log()
    }
}
?>