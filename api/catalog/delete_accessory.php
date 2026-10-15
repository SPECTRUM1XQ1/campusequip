<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Equipment.php";

require_post();
require_permission('can_manage_catalog');

$accessoryId = post_int('accessory_id');
if ($accessoryId <= 0) {
    json_response(['success' => false, 'message' => 'Accessory ID is required.']);
}

json_response((new Equipment())->removeAccessory($accessoryId));
