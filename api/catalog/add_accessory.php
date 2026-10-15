<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Equipment.php";

require_post();
require_permission('can_manage_catalog');

$catalogId = post_int('catalog_id');
$name      = post_string('accessory_name');

if ($catalogId <= 0) {
    json_response(['success' => false, 'message' => 'Catalog ID is required.']);
}

json_response((new Equipment())->addAccessory($catalogId, $name));
