<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Reservation.php';

require_post();
$user = require_permission('can_approve_requests');

$reservationId = post_int('reservation_id');
$itemId = post_int('item_id');

if ($reservationId <= 0) {
    json_response(['success' => false,'message'=> 'Reservation Id Not Found']);
}

if ($itemId <= 0) { 
    json_response(['success'=> false,'message'=> 'Item Id is not Found']);
}

$result = (new Reservation())->approve($reservationId, $itemId, (int) $user['user_id']);

json_response($result);