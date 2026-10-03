<?PHP 
class Fine
{
    private PDO $db;
    private TransactionLog $logger;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->logger = new TransactionLog();
    }

    public function charge(int $reservationId, float $amount): array {}
    public function markPaid(int $fineId, int $staffId): array {}
    public function waive(int $fineId, int $staffId, ?string $reason = null): array {}
    public function getByReservation(int $reservationId): ?array {}
    public function listUnpaid(): array {}
}
?>