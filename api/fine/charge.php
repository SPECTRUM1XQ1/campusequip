<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Fine.php';

require_post();
$user = require_role(['admin']); // only administrators manage fines; staff can view them

$checkoutId = post_int('checkout_id');
$rawAmount = $_POST['amount_due'] ?? null;
$reason = post_string('reason');
$type   = post_string('fine_type', 'other');

// 'late' fines are created automatically when a late item is returned; staff add the rest.
if (!in_array($type, Fine::MANUAL_TYPES, true)) {
    json_response(['success' => false, 'message' => 'Please choose a valid fine type.'], 400);
}

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

// Limit: 5 digits before the decimal point.
if ($amount > 99999.99) {
    json_response(['success' => false, 'message' => 'Fine amount cannot be more than 99,999.99 (5 digits).']);
}

$result = (new Fine())->charge($checkoutId, (int) $user['user_id'], $amount, $reason !== '' ? $reason : null, $type);

json_response($result);