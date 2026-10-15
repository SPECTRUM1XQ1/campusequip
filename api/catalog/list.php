<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Equipment.php';

// Catalog listing can typically be viewed by any logged-in user (or public depending on rules, 
// but let's ensure session context or standard helper access is safe).
// If your catalog is public to browse, you can omit require_permission() or adjust as needed.

// 1. Read optional query parameters for filtering
$search = $_GET['search'] ?? null;
$categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int) $_GET['category_id'] : null;
$includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';

// 2. Fetch catalog items using the Equipment model
$equipment = new Equipment();
$catalogItems = $equipment->listCatalog($search, $categoryId, $includeArchived);

// 3. Return successful JSON response
json_response([
    'success' => true,
    'data' => $catalogItems
]);