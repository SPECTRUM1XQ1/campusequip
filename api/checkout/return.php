<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Checkout.php";

require_post();
$user = require_permission('can_inspect_returns');

$checkoutId        = post_int('checkout_id');
$returnCondition   = post_string('return_condition');
$damageNotes       = post_string_or_null('damage_notes');          // "what happened" (damaged only)
$inspectionRemarks = post_string_or_null('inspection_remarks');    // optional extra remarks
$missingAccessoryIds = post_int_array('missing_accessories');      // checkboxes: missing_accessories[]

if ($checkoutId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid or missing Checkout ID']);
}
if ($returnCondition === '') {
    json_response(['success' => false, 'message' => 'Return condition is required']);
}

// Checkout::processReturn() enforces the per-condition rules:
//   damaged             -> damage_notes required
//   missing_accessories -> at least one accessory, all belonging to that unit's model
$result = (new Checkout())->processReturn(
    $checkoutId,
    (int) $user['user_id'],
    $returnCondition,
    $damageNotes,
    $inspectionRemarks,
    $missingAccessoryIds
);

json_response($result);
