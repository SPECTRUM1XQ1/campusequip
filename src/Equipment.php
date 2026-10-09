<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';

class Equipment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listCategories(): array
    {
        $stm = $this->db->query('SELECT category_id, category_name FROM categories ORDER BY category_name ASC');
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    public function listCatalog(?string $search = null, ?int $categoryId = null, bool $includeArchived = false): array
    {
        $conditions = [];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $conditions[] = 'ec.model_name LIKE :search';
            $params[':search'] = '%' . trim($search) . '%';
        }
        if ($categoryId !== null) {
            $conditions[] = 'ec.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }
        if (!$includeArchived) {
            $conditions[] = 'ec.is_archived = 0';
        }

        $sql = "SELECT ec.*, c.category_name,
                       SUM(CASE WHEN si.status IS NOT NULL AND si.status <> 'retired' THEN 1 ELSE 0 END) AS total_units,
                       SUM(CASE WHEN si.status = 'available' THEN 1 ELSE 0 END) AS available_units
                FROM equipment_catalog ec
                LEFT JOIN categories c ON c.category_id = ec.category_id
                LEFT JOIN serialized_items si ON si.catalog_id = ec.catalog_id";

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' GROUP BY ec.catalog_id, c.category_name ORDER BY ec.model_name ASC';

        $stm = $this->db->prepare($sql);
        $stm->execute($params);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getCatalogDetail(int $catalogId): ?array
    {
        $stm = $this->db->prepare(
            "SELECT ec.*, c.category_name,
                    SUM(CASE WHEN si.status IS NOT NULL AND si.status <> 'retired' THEN 1 ELSE 0 END) AS total_units,
                    SUM(CASE WHEN si.status = 'available' THEN 1 ELSE 0 END) AS available_units
             FROM equipment_catalog ec
             LEFT JOIN categories c ON c.category_id = ec.category_id
             LEFT JOIN serialized_items si ON si.catalog_id = ec.catalog_id
             WHERE ec.catalog_id = :id
             GROUP BY ec.catalog_id, c.category_name"
        );
        $stm->execute([':id' => $catalogId]);
        return $stm->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listUnits(int $catalogId): array
    {
        $stm = $this->db->prepare(
            'SELECT item_id, serial_number, status, storage_location, condition_notes
             FROM serialized_items
             WHERE catalog_id = :catalog_id
             ORDER BY serial_number ASC'
        );
        $stm->execute([':catalog_id' => $catalogId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addCatalogModel(array $data): array
    {
        $modelName    = trim((string) ($data['model_name'] ?? ''));
        $categoryId   = (int) ($data['category_id'] ?? 0);
        $maxLoanDays  = (int) ($data['max_loan_days'] ?? 3);
        $lateFineRate = (float) ($data['late_fine_rate'] ?? 5.00);
        $description  = $data['description'] ?? null;

        if ($modelName === '') {
            return ['success' => false, 'message' => 'Model name is required.'];
        }
        if ($categoryId <= 0) {
            return ['success' => false, 'message' => 'Please choose a category.'];
        }
        if ($maxLoanDays < 1) {
            return ['success' => false, 'message' => 'Maximum loan days must be at least 1.'];
        }
        if ($lateFineRate < 0) {
            return ['success' => false, 'message' => 'Late fine rate cannot be negative.'];
        }

        try {
            $stm = $this->db->prepare(
                'INSERT INTO equipment_catalog (model_name, category_id, max_loan_days, late_fine_rate, description)
                 VALUES (:model_name, :category_id, :max_loan_days, :late_fine_rate, :description)'
            );
            $stm->execute([
                ':model_name'     => $modelName,
                ':category_id'    => $categoryId,
                ':max_loan_days'  => $maxLoanDays,
                ':late_fine_rate' => $lateFineRate,
                ':description'    => $description,
            ]);
        } catch (PDOException $e) {
            // errorInfo[1] is MySQL's own error number. 1452 = foreign key failed.
            if ((int) ($e->errorInfo[1] ?? 0) === 1452) {
                return ['success' => false, 'message' => 'That category does not exist.'];
            }
            error_log('Equipment::addCatalogModel failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not add the equipment model.'];
        }

        return ['success' => true, 'catalog_id' => (int) $this->db->lastInsertId()];
    }

    public function addSerializedUnit(int $catalogId, string $serialNumber, ?string $notes = null, ?string $storageLocation = null): array
    {
        $serialNumber = trim($serialNumber);
        if ($serialNumber === '') {
            return ['success' => false, 'message' => 'Serial number is required.'];
        }

        try {
            $stm = $this->db->prepare(
                'INSERT INTO serialized_items (catalog_id, serial_number, status, storage_location, condition_notes, created_at)
                 VALUES (:catalog_id, :serial_number, :status, :storage_location, :notes, NOW())'
            );
            $stm->execute([
                ':catalog_id'       => $catalogId,
                ':serial_number'    => $serialNumber,
                ':status'           => 'available',
                ':storage_location' => $storageLocation,
                ':notes'            => $notes,
            ]);
        } catch (PDOException $e) {
            $code = (int) ($e->errorInfo[1] ?? 0);
            if ($code === 1062) { // duplicate entry (the UNIQUE serial_number)
                return ['success' => false, 'message' => 'That serial number already exists.'];
            }
            if ($code === 1452) { // foreign key failed (catalog_id does not exist)
                return ['success' => false, 'message' => 'That equipment model does not exist.'];
            }
            error_log('Equipment::addSerializedUnit failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not add the unit.'];
        }

        return ['success' => true, 'item_id' => (int) $this->db->lastInsertId()];
    }
    public function updateUnitStatus(int $itemId, string $status, ?string $notes = null): array
    {
        if (!in_array($status, ['available', 'maintenance', 'retired'], true)) {
            return ['success' => false, 'message' => 'Invalid unit status.'];
        }

        try {
            $this->db->beginTransaction();

            $stm = $this->db->prepare('SELECT status FROM serialized_items WHERE item_id = :id FOR UPDATE');
            $stm->execute([':id' => $itemId]);
            $current = $stm->fetchColumn();

            if ($current === false) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Unit not found.'];
            }

            // Check the real data too, not just the status flag.
            $stm = $this->db->prepare('SELECT checkout_id FROM checkouts WHERE item_id = :id AND returned_at IS NULL LIMIT 1');
            $stm->execute([':id' => $itemId]);
            if ($current === 'checked_out' || $stm->fetch()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'This unit is currently checked out. Process the return first.'];
            }

            $stm = $this->db->prepare(
                'UPDATE serialized_items
                 SET status = :status, condition_notes = COALESCE(:notes, condition_notes)
                 WHERE item_id = :id'
            );
            $stm->execute([':status' => $status, ':notes' => $notes, ':id' => $itemId]);

            $this->db->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Equipment::updateUnitStatus failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not update the unit.'];
        }
    }
}