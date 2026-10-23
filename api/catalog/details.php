<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Equipment.php';

current_user(); // signed-in users only (the catalog is not public)

$catalogId = isset($_GET['catalog_id']) ? (int) $_GET['catalog_id'] : 0;

if ($catalogId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid or missing Catalog ID']);
}

$equipment = new Equipment();
$details = $equipment->getCatalogDetail($catalogId);

// Check if the equipment model actually exists in the database
if (!$details) {
    json_response(['success' => false, 'message' => 'Equipment model not found']);
}

json_response([
    'success' => true, 
    'details' => $details, 
    'unit' => $equipment->listUnits($catalogId)
]);