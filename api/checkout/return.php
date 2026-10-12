<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Checkout.php";

require_post();
$user = require_permission('can_inspect_returns');

$checkoutId = post_int('checkout_id');
$returnCondition = post_string('return_condition');
$damageNotes = post_string('damage_notes');
$inspectionRemarks = post_string('inspection_remarks');

if ($checkoutId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid or missing Checkout ID'], 400);
}

if (empty($returnCondition)) {
    json_response(['success' => false, 'message' => 'Return condition is required'], 400);
}

$result = (new Checkout())->processReturn($checkoutId, (int) $user['user_id'], $returnCondition, $damageNotes, $inspectionRemarks);

json_response($result);