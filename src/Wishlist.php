<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/Database.php';

/**
 * A borrower's saved items ("watchlist"). One row per (user, equipment model).
 */
class Wishlist
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Saved models with live availability, newest saved first. */
    public function listForUser(int $userId): array
    {
        $stm = $this->db->prepare(
            "SELECT w.wishlist_id, w.created_at AS saved_at,
                    ec.catalog_id, ec.model_name, ec.max_loan_days, ec.description, ec.is_archived,
                    c.category_name,
                    COALESCE(SUM(CASE WHEN si.status = 'available' THEN 1 ELSE 0 END), 0)   AS available_units,
                    COALESCE(SUM(CASE WHEN si.status = 'maintenance' THEN 1 ELSE 0 END), 0) AS maintenance_units,
                    COALESCE(SUM(CASE WHEN si.status IS NOT NULL AND si.status <> 'retired' THEN 1 ELSE 0 END), 0) AS total_units
             FROM wishlist_items w
             JOIN equipment_catalog ec ON ec.catalog_id = w.catalog_id
             LEFT JOIN categories c ON c.category_id = ec.category_id
             LEFT JOIN serialized_items si ON si.catalog_id = ec.catalog_id
             WHERE w.user_id = :user_id
             GROUP BY w.wishlist_id, w.created_at, ec.catalog_id, c.category_name
             ORDER BY w.created_at DESC, w.wishlist_id DESC"
        );
        $stm->execute([':user_id' => $userId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Just the catalog ids this user has saved (for the heart/save buttons). */
    public function savedCatalogIds(int $userId): array
    {
        $stm = $this->db->prepare('SELECT catalog_id FROM wishlist_items WHERE user_id = :user_id');
        $stm->execute([':user_id' => $userId]);
        return array_map('intval', $stm->fetchAll(PDO::FETCH_COLUMN));
    }

    public function add(int $userId, int $catalogId): array
    {
        try {
            $stm = $this->db->prepare('SELECT is_archived FROM equipment_catalog WHERE catalog_id = :id');
            $stm->execute([':id' => $catalogId]);
            $archived = $stm->fetchColumn();

            if ($archived === false) {
                return ['success' => false, 'message' => 'That equipment model was not found.'];
            }
            if ((int) $archived === 1) {
                return ['success' => false, 'message' => 'That equipment is no longer offered.'];
            }

            // INSERT IGNORE: saving something twice is harmless (unique key user+model).
            $stm = $this->db->prepare('INSERT IGNORE INTO wishlist_items (user_id, catalog_id) VALUES (:user_id, :catalog_id)');
            $stm->execute([':user_id' => $userId, ':catalog_id' => $catalogId]);

            return ['success' => true];
        } catch (Throwable $e) {
            error_log('Wishlist::add failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not save the item. Please try again.'];
        }
    }

    /** Remove one or many saved models. Only ever touches this user's own rows. */
    public function remove(int $userId, array $catalogIds): array
    {
        $catalogIds = array_values(array_unique(array_filter(array_map('intval', $catalogIds), fn($id) => $id > 0)));
        if (empty($catalogIds)) {
            return ['success' => false, 'message' => 'No items selected.'];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($catalogIds), '?'));
            $stm = $this->db->prepare(
                "DELETE FROM wishlist_items WHERE user_id = ? AND catalog_id IN ($placeholders)"
            );
            $stm->execute(array_merge([$userId], $catalogIds));

            return ['success' => true, 'removed' => $stm->rowCount()];
        } catch (Throwable $e) {
            error_log('Wishlist::remove failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not remove the item. Please try again.'];
        }
    }

    public function clear(int $userId): array
    {
        try {
            $stm = $this->db->prepare('DELETE FROM wishlist_items WHERE user_id = :user_id');
            $stm->execute([':user_id' => $userId]);

            return ['success' => true, 'removed' => $stm->rowCount()];
        } catch (Throwable $e) {
            error_log('Wishlist::clear failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not clear your saved items. Please try again.'];
        }
    }
}
