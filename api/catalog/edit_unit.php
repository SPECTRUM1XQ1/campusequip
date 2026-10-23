<?php
// Edit a physical unit's details. (api/catalog/update_unit.php stays as the quick status-only change.)
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Equipment.php";

require_post();
require_permission('can_manage_catalog');

$itemId  = post_int('item_id');
$serial  = post_string('serial_number');
$storage = post_string_or_null('storage_location');
$notes   = post_string_or_null('condition_notes');
$status  = post_string_or_null('status');   // optional: omitted for units that are out on loan

if ($itemId <= 0) {
    json_response(['success' => false, 'message' => 'Item ID is required.']);
}

json_response((new Equipment())->updateUnit($itemId, $serial, $storage, $notes, $status));
