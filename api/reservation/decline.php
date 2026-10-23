<?php 
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Reservation.php';

require_post();
$user = require_permission('can_approve_requests');

$reservationId = post_int('reservation_id');
$reason        = post_string('decline_reason');

if ($reservationId <= 0) {
    json_response(['success' => false, 'message' => 'Reservation ID not found.']);
}

if (empty($reason)) {
    json_response(['success' => false, 'message' => 'Please provide a decline reason.']);
}

$result = (new Reservation())->decline($reservationId, (int) $user['user_id'], $reason);
json_response($result);