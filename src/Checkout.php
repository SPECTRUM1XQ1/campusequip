<?php 
class Checkout
{
    private PDO $db;
    private TransactionLog $logger;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->logger = new TransactionLog();
    }

    public function handover(int $reservationId, int $issuedBy, bool $idVerified): array {}
    public function processReturn(int $checkoutId, int $receivedBy, string $returnCondition, ?string $damageNotes = null, ?string $inspectionRemarks = null): array {}
    public function getByReservation(int $reservationId): ?array {}
    public function listActive(): array {}
    public function listOverdue(): array {}
}
?>