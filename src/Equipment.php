<?php 
if(!defined('APP_INIT')){http_response_code(404); exit;}

require_once __DIR__ .'/../config/Database.php';
class Equipment {
    private PDO $db;
    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function listCategories(?string $search = null, ?int $categoryId = null): array {
        $stm = $this->db->query('SELECT *  FROM categories ORDER BY category_name ASC');
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }   

    public function listCatalog(?string $search = null, ?int $categoryId = null): array {
        $condition = [];
        $param = [];
        if ($search !== null) {
            $condition[] = 'model_name LIKE :search';
            $param[] = '%'. $search .'%';
        }
            
        if ($categoryId !== null) {
            $condition[] = 'category_id = :id';
            $param[':id'] = $categoryId;
        }
        $sql = 'SELECT * FROM equipmwnt_catalog';  
        if (!empty($condition)) {
            $sql .= ' WHERE '. implode(' AND ', $condition);
        }
        $stm = $this->db->prepare($sql);
        $stm->execute($param);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getCatalogDetail(int $catalogId): ?array {
        $stm = $this->db->prepare(
            "SELECT ec.*, c.category_name, 
            COUNT(si.item_id) AS total_units,
            SUM(CASE WHEN si.status = 'available' THEN 1 ELSE 0 END) AS available_units
            FROM equipment_catalog ec
            LEFT JOIN categories c ON ec.category_id = c.category_id 
            LEFT JOIN serialized_items si ON ec.catalog_id = si.catalog_id
            WHERE ec.catalog_id = :id GROUP BY ec.catalog_id");
        $stm->execute([':id' => $catalogId]);
        return $stm->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function addCatalogModel(array $data): array {
        $stm = $this->db->prepare("INSERT INTO equipment_catalog(model_name, category_id, max_loan_days, late_fine_rate, description, created_at) 
        VALUES (:model_name. :category_id, :max_loan_day, :late_fine_rate. :description)");

        $stm->execute([
            ':model_name' => $data,
            ':category_id' => $data,
            ':max_loan_day' => $data,
            ':late_fine' => $data,
            ':discription' => $data
        ]); 

        return['success'=> true,'category_id'=> $data['category_id']];
    }
    public function addSerializedUnit(int $catalogId, ?string $notes, string $serialNumber, ?string $storageLocation = null): array {
        $stm = $this->db->prepare(
            "INSERT INTO serialized_items(catalog_id, serial_number, status, storage_location, condition_notes, created_at) 
             VALUES (:id, :sn, :status, :sl, :notes, NOW())                   
        ");
        $stm->execute([
            ':id' => $catalogId,
            ':sn' => $serialNumber,
            ':status' => 'available',
            ':sl'=> $storageLocation,
            ':notes' => $notes
        ]);
        $serializeId = (int) $this->db->lastInsertId();

        return ['success' => true, 'serialize_id'=> $serializeId ];
    }
}
?>