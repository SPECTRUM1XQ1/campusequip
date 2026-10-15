<?php

require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Equipment.php";

require_post();
$user = require_permission('can_manage_catalog');

$itemId = post_int('item_id');
$status = post_string('status');
$notes  = post_string_or_null('condition_notes');

if ($itemId <= 0) {
    json_response(['success' => false, 'message' => 'Item ID not found.']);
}

// 'reserved' and 'checked_out' are managed by the system (approve / handover),
// so only these three can be set by hand. Matches Equipment::updateUnitStatus().
if (!in_array($status, ['available', 'maintenance', 'retired'], true)) {
    json_response(['success' => false, 'message' => 'Invalid status.']);
}

json_response((new Equipment())->updateUnitStatus($itemId, $status, $notes));
