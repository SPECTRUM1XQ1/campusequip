<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Fine.php';

require_post();
$user = require_permission('can_inspect_returns');

$checkoutId = post_int('checkout_id');
$rawAmount = $_POST['amount'] ?? null;
$reason = post_string('reason');

if (!is_string($rawAmount) && !is_int($rawAmount) && !is_float($rawAmount)) {
    json_response(['success' => false, 'message' => 'Amount must be a number.'], 400);
}
if (!is_numeric($rawAmount)) {
    json_response(['success' => false, 'message' => 'Amount must be a number.'], 400);
}

// Store currency amounts to two decimal places (for example, 10.456 becomes 10.46).
$amount = round((float) $rawAmount, 2);

if ($checkoutId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid or missing Checkout ID']);
}

if ($amount <= 0) {
    json_response(['success' => false, 'message' => 'Fine amount must be greater than zero']);
}

$result = (new Fine())->charge($checkoutId, (int) $user['user_id'], $amount, $reason !== '' ? $reason : null);

json_response($result);