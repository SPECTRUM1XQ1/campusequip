<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Equipment.php";

require_post();
require_permission('can_manage_catalog');

$catalogId = post_int('catalog_id');
if ($catalogId <= 0) {
    json_response(['success' => false, 'message' => 'Catalog ID is required.']);
}

// Keys must match what Equipment::updateCatalogModel() reads.
$data = [
    'model_name'     => post_string('model_name'),
    'category_id'    => post_int('category_id'),
    'max_loan_days'  => post_int('max_loan_days', 3),
    'late_fine_rate' => post_float('late_fine_rate', 5.00),
    'description'    => post_string_or_null('description'),
];

json_response((new Equipment())->updateCatalogModel($catalogId, $data));
