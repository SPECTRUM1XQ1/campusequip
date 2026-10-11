<?php 
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Reservation.php';


require_post();

$user = require_role(['borrower']);

$reservationId = post_int('reservation_id');

if ($reservationId <= 0) {
    json_response(['success' => false, 'message' => 'invalid Reservation Id']);
}

$result = (new Reservation())->cancel($reservationId, (int) $user['user_id']);

json_response($result);