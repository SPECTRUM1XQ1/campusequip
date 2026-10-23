<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Reservation.php';
require_once __DIR__ . '/../../src/Settings.php';

require_post();
$user = require_role(['borrower']);

// Admin switched maintenance mode on: no new requests until it is turned off.
if (Settings::isMaintenance()) {
    json_response(['success' => false, 'message' => Settings::maintenanceMessage()]);
}

// Read the inputs (take the values out of $_POST).
$catalogId = post_int('catalog_id');
$startAt   = post_string('start_at');
$days      = post_int('days');
$endAt     = post_string('end_at'); // still accepted for older callers
$purpose   = post_string('purpose');

// The borrower picks a number of days; the end date is worked out here so it
// can never disagree with the model's max loan limit (checked in Reservation::create).
if ($days > 0) {
    $startTs = strtotime($startAt);
    if ($startTs === false) {
        json_response(['success' => false, 'message' => 'Invalid date format.']);
    }
    $endAt = date('Y-m-d', strtotime('+' . $days . ' days', $startTs));
}

if (!post_has('terms_accepted')) {
    json_response(['success' => false, 'message' => 'You must accept the borrowing terms.']);
}
if ($catalogId <= 0) {
    json_response(['success' => false, 'message' => 'Please choose an equipment model.']);
}

// The borrower id comes from the session, never from $_POST.
$result = (new Reservation())->create((int) $user['user_id'], $catalogId, $startAt, $endAt, $purpose);

json_response($result);