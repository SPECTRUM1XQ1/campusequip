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
        if (!$search === null) {
            $stm = $this->db->query('SELECT model_name FROM equipment_catalog WH');
            $stm->execute();
        }

        if (!$categoryId === null) {
            $stm = $this->db->prepare('SELECT ');
            $stm->execute([''=> $categoryId]);
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        }  
        return['success' => true, 'category_id' => $categoryId];
    }
    public function getCatalogDetail(int $catalogId): ?array {

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