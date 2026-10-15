<?php
// GET api/catalog/accessories.php?catalog_id=3  ->  accessories that ship with that model.
// Any staff/admin may read (the return form needs it); editing needs can_manage_catalog.
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Equipment.php";

require_role(['admin', 'staff']);

$catalogId = isset($_GET['catalog_id']) ? (int) $_GET['catalog_id'] : 0;
if ($catalogId <= 0) {
    json_response(['success' => false, 'message' => 'Catalog ID is required.']);
}

json_response([
    'success' => true,
    'data'    => (new Equipment())->listAccessories($catalogId),
]);
