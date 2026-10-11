<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Reservation.php';

require_post();
$user = require_role(['borrower']);

// Read the inputs (take the values out of $_POST).
$catalogId = post_int('catalog_id');
$startAt   = post_string('start_at');
$endAt     = post_string('end_at');
$purpose   = post_string('purpose');

if (!post_has('terms_accepted')) {
    json_response(['success' => false, 'message' => 'You must accept the borrowing terms.']);
}
if ($catalogId <= 0) {
    json_response(['success' => false, 'message' => 'Please choose an equipment model.']);
}

// The borrower id comes from the session, never from $_POST.
$result = (new Reservation())->create((int) $user['user_id'], $catalogId, $startAt, $endAt, $purpose);

json_response($result);