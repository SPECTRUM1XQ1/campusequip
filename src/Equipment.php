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
    /** Add a new equipment category. Names are unique (ignoring upper/lower case). */
    public function addCategory(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
            return ['success' => false, 'message' => 'Category name must be 2 to 50 characters.'];
        }

        try {
            $stm = $this->db->prepare('SELECT category_id FROM categories WHERE LOWER(category_name) = LOWER(:name) LIMIT 1');
            $stm->execute([':name' => $name]);
            if ($stm->fetchColumn() !== false) {
                return ['success' => false, 'message' => 'That category already exists.'];
            }

            $stm = $this->db->prepare('INSERT INTO categories (category_name) VALUES (:name)');
            $stm->execute([':name' => $name]);

            return [
                'success'  => true,
                'category' => ['category_id' => (int) $this->db->lastInsertId(), 'category_name' => $name],
            ];
        } catch (Throwable $e) {
            error_log('Equipment::addCategory failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not add the category. Please try again.'];
        }
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

    /** Models with units, biggest first. $limit keeps the old default of 5; the dashboard asks for more and pages them. */
    public function getAvailabilityBreakdown(int $limit = 5): array
    {
        $sql = "SELECT ec.catalog_id, ec.model_name,
                       COUNT(si.item_id) AS total_units,
                       SUM(CASE WHEN si.status = 'available' THEN 1 ELSE 0 END) AS available_units,
                       SUM(CASE WHEN si.status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_units,
                       SUM(CASE WHEN si.status = 'checked_out' THEN 1 ELSE 0 END) AS checked_out_units
                FROM equipment_catalog ec
                LEFT JOIN serialized_items si ON si.catalog_id = ec.catalog_id
                WHERE ec.is_archived = 0
                GROUP BY ec.catalog_id, ec.model_name
                HAVING total_units > 0
                ORDER BY total_units DESC LIMIT " . max(1, $limit);
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function listAllSerialized(?int $catalogId = null): array
    {
        $sql = "SELECT si.item_id, si.catalog_id, si.serial_number, si.status,
                       si.storage_location, si.condition_notes, ec.model_name, ec.is_archived, c.category_name
                FROM serialized_items si
                JOIN equipment_catalog ec ON ec.catalog_id = si.catalog_id
                LEFT JOIN categories c ON c.category_id = ec.category_id";
        $params = [];
        if ($catalogId !== null) {
            $sql .= ' WHERE si.catalog_id = :catalog_id';
            $params[':catalog_id'] = $catalogId;
        }
        $sql .= ' ORDER BY si.created_at DESC, si.item_id DESC';

        $stm = $this->db->prepare($sql);
        $stm->execute($params);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
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

    /**
     * Validates and normalises the model fields shared by add and edit.
     * Returns [errorMessage|null, cleanedFields|null].
     */
    private function cleanModelData(array $data): array
    {
        $modelName    = trim((string) ($data['model_name'] ?? ''));
        $categoryId   = (int) ($data['category_id'] ?? 0);
        $maxLoanDays  = (int) ($data['max_loan_days'] ?? 3);
        $lateFineRate = (float) ($data['late_fine_rate'] ?? 5.00);
        $description  = isset($data['description']) ? trim((string) $data['description']) : null;
        if ($description === '') {
            $description = null;
        }

        if ($modelName === '') {
            return ['Model name is required.', null];
        }
        // mbstring is optional on some PHP installs; byte length is stricter, never looser.
        $nameLength = function_exists('mb_strlen') ? mb_strlen($modelName) : strlen($modelName);
        if ($nameLength > 150) {
            return ['Model name must be 150 characters or less.', null];
        }
        if ($categoryId <= 0) {
            return ['Please choose a category.', null];
        }
        if ($maxLoanDays < 1) {
            return ['Maximum loan days must be at least 1.', null];
        }
        if ($lateFineRate < 0) {
            return ['Late fine rate cannot be negative.', null];
        }

        return [null, [
            'model_name'     => $modelName,
            'category_id'    => $categoryId,
            'max_loan_days'  => $maxLoanDays,
            'late_fine_rate' => $lateFineRate,
            'description'    => $description,
        ]];
    }

    public function addCatalogModel(array $data): array
    {
        [$error, $m] = $this->cleanModelData($data);
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        try {
            $stm = $this->db->prepare(
                'INSERT INTO equipment_catalog (model_name, category_id, max_loan_days, late_fine_rate, description)
                 VALUES (:model_name, :category_id, :max_loan_days, :late_fine_rate, :description)'
            );
            $stm->execute([
                ':model_name'     => $m['model_name'],
                ':category_id'    => $m['category_id'],
                ':max_loan_days'  => $m['max_loan_days'],
                ':late_fine_rate' => $m['late_fine_rate'],
                ':description'    => $m['description'],
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

    /** Edit an existing model's details. Changes apply to FUTURE reservations only. */
    public function updateCatalogModel(int $catalogId, array $data): array
    {
        [$error, $m] = $this->cleanModelData($data);
        if ($error !== null) {
            return ['success' => false, 'message' => $error];
        }

        try {
            $stm = $this->db->prepare('SELECT 1 FROM equipment_catalog WHERE catalog_id = :id');
            $stm->execute([':id' => $catalogId]);
            if (!$stm->fetchColumn()) {
                return ['success' => false, 'message' => 'Equipment model not found.'];
            }

            $stm = $this->db->prepare(
                'UPDATE equipment_catalog
                 SET model_name = :model_name, category_id = :category_id, max_loan_days = :max_loan_days,
                     late_fine_rate = :late_fine_rate, description = :description
                 WHERE catalog_id = :id'
            );
            $stm->execute([
                ':model_name'     => $m['model_name'],
                ':category_id'    => $m['category_id'],
                ':max_loan_days'  => $m['max_loan_days'],
                ':late_fine_rate' => $m['late_fine_rate'],
                ':description'    => $m['description'],
                ':id'             => $catalogId,
            ]);
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1452) {
                return ['success' => false, 'message' => 'That category does not exist.'];
            }
            error_log('Equipment::updateCatalogModel failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not update the equipment model.'];
        }

        return ['success' => true];
    }

    /**
     * Archive (hide from borrowers, block new reservations) or restore a model.
     * Archiving is refused while any reservation for the model is still open,
     * because those borrowers still expect the equipment.
     */
    public function setCatalogArchived(int $catalogId, bool $archived): array
    {
        try {
            $this->db->beginTransaction();

            $stm = $this->db->prepare('SELECT is_archived FROM equipment_catalog WHERE catalog_id = :id FOR UPDATE');
            $stm->execute([':id' => $catalogId]);
            $current = $stm->fetchColumn();

            if ($current === false) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Equipment model not found.'];
            }
            if ((bool) (int) $current === $archived) {
                $this->db->rollBack();
                return ['success' => true, 'message' => $archived ? 'Already archived.' : 'Already active.'];
            }

            if ($archived) {
                $stm = $this->db->prepare(
                    "SELECT COUNT(*) FROM reservations
                     WHERE catalog_id = :id AND status IN ('pending', 'approved', 'checked_out')"
                );
                $stm->execute([':id' => $catalogId]);
                $open = (int) $stm->fetchColumn();
                if ($open > 0) {
                    $this->db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Cannot archive: $open open reservation(s) (pending, approved or checked out) still use this model. Resolve them first.",
                    ];
                }
            }

            $stm = $this->db->prepare(
                'UPDATE equipment_catalog SET is_archived = :flag WHERE catalog_id = :id'
            );
            $stm->execute([':flag' => $archived ? 1 : 0, ':id' => $catalogId]);

            $this->db->commit();
            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Equipment::setCatalogArchived failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not change the archive status.'];
        }
    }

    /**
     * Edit a physical unit: serial number, storage location, notes and (optionally) status.
     * Serial number and status are locked while the unit is out on loan; storage and notes
     * can always be corrected.
     */
    public function updateUnit(int $itemId, string $serialNumber, ?string $storageLocation, ?string $notes, ?string $status = null): array
    {
        $serialNumber = trim($serialNumber);
        if ($serialNumber === '') {
            return ['success' => false, 'message' => 'Serial number is required.'];
        }
        $serialLength = function_exists('mb_strlen') ? mb_strlen($serialNumber) : strlen($serialNumber);
        if ($serialLength > 100) {
            return ['success' => false, 'message' => 'Serial number must be 100 characters or less.'];
        }
        if ($storageLocation !== null) {
            $storageLocation = trim($storageLocation);
            $storageLocation = ($storageLocation === '') ? null : $storageLocation;
        }
        if ($storageLocation !== null && (function_exists('mb_strlen') ? mb_strlen($storageLocation) : strlen($storageLocation)) > 100) {
            return ['success' => false, 'message' => 'Storage location must be 100 characters or less.'];
        }
        if ($notes !== null) {
            $notes = trim($notes);
            $notes = ($notes === '') ? null : $notes;
        }
        if ($status !== null && !in_array($status, ['available', 'maintenance', 'retired'], true)) {
            return ['success' => false, 'message' => 'Invalid unit status.'];
        }

        try {
            $this->db->beginTransaction();

            $stm = $this->db->prepare('SELECT serial_number, status FROM serialized_items WHERE item_id = :id FOR UPDATE');
            $stm->execute([':id' => $itemId]);
            $unit = $stm->fetch(PDO::FETCH_ASSOC);
            if (!$unit) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Unit not found.'];
            }

            // Check the real data too, not just the status flag.
            $stm = $this->db->prepare('SELECT 1 FROM checkouts WHERE item_id = :id AND returned_at IS NULL LIMIT 1');
            $stm->execute([':id' => $itemId]);
            $onLoan = ($unit['status'] === 'checked_out') || (bool) $stm->fetchColumn();

            if ($serialNumber !== $unit['serial_number'] && $onLoan) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'The serial number cannot be changed while the unit is checked out.'];
            }

            $newStatus = $unit['status'];
            if ($status !== null && $status !== $unit['status']) {
                if ($onLoan) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'This unit is currently checked out. Process the return first.'];
                }
                if (in_array($status, ['maintenance', 'retired'], true)) {
                    $stm = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE item_id = :id AND status = 'approved'");
                    $stm->execute([':id' => $itemId]);
                    if ((int) $stm->fetchColumn() > 0) {
                        $this->db->rollBack();
                        return ['success' => false, 'message' => 'This unit is assigned to an approved reservation. Cancel or reassign that reservation first.'];
                    }
                }
                $newStatus = $status;
            }

            $stm = $this->db->prepare(
                'UPDATE serialized_items
                 SET serial_number = :serial, storage_location = :storage, condition_notes = :notes, status = :status
                 WHERE item_id = :id'
            );
            $stm->execute([
                ':serial'  => $serialNumber,
                ':storage' => $storageLocation,
                ':notes'   => $notes,
                ':status'  => $newStatus,
                ':id'      => $itemId,
            ]);

            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) { // UNIQUE serial_number
                return ['success' => false, 'message' => 'That serial number already exists.'];
            }
            error_log('Equipment::updateUnit failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not update the unit.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Equipment::updateUnit failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not update the unit.'];
        }
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
    // ---- Accessories (items that ship with a model) ----

    public function listAccessories(int $catalogId): array
    {
        $stm = $this->db->prepare(
            'SELECT accessory_id, catalog_id, accessory_name
             FROM accessories
             WHERE catalog_id = :catalog_id
             ORDER BY accessory_name ASC'
        );
        $stm->execute([':catalog_id' => $catalogId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Every accessory, grouped by model: [catalog_id => [ {accessory_id, accessory_name}, ... ]].
     * One query for pages that need the list for many models at once.
     */
    public function listAccessoriesGrouped(): array
    {
        $rows = $this->db->query(
            'SELECT accessory_id, catalog_id, accessory_name
             FROM accessories
             ORDER BY accessory_name ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['catalog_id']][] = [
                'accessory_id'   => (int) $row['accessory_id'],
                'accessory_name' => $row['accessory_name'],
            ];
        }
        return $grouped;
    }

    public function addAccessory(int $catalogId, string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['success' => false, 'message' => 'Accessory name is required.'];
        }
        // mbstring is optional on some PHP installs; fall back to byte length (stricter, never looser).
        $length = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
        if ($length > 100) {
            return ['success' => false, 'message' => 'Accessory name must be 100 characters or less.'];
        }

        try {
            $stm = $this->db->prepare(
                'INSERT INTO accessories (catalog_id, accessory_name) VALUES (:catalog_id, :name)'
            );
            $stm->execute([':catalog_id' => $catalogId, ':name' => $name]);
        } catch (PDOException $e) {
            $code = (int) ($e->errorInfo[1] ?? 0);
            if ($code === 1062) { // UNIQUE (catalog_id, accessory_name)
                return ['success' => false, 'message' => 'That accessory is already listed for this model.'];
            }
            if ($code === 1452) { // catalog_id does not exist
                return ['success' => false, 'message' => 'That equipment model does not exist.'];
            }
            error_log('Equipment::addAccessory failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not add the accessory.'];
        }

        return ['success' => true, 'accessory_id' => (int) $this->db->lastInsertId()];
    }

    public function removeAccessory(int $accessoryId): array
    {
        try {
            $stm = $this->db->prepare('DELETE FROM accessories WHERE accessory_id = :id');
            $stm->execute([':id' => $accessoryId]);
        } catch (PDOException $e) {
            // 1451 = a row in checkout_missing_accessories still points at it.
            // The history is permanent, so the accessory can't be deleted.
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return ['success' => false, 'message' => 'This accessory appears in past return records and cannot be deleted.'];
            }
            error_log('Equipment::removeAccessory failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not remove the accessory.'];
        }

        if ($stm->rowCount() === 0) {
            return ['success' => false, 'message' => 'Accessory not found.'];
        }
        return ['success' => true];
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