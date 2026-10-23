<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Checkout.php";
    
require_post();
$user = require_permission("can_confirm_pickup");

$reservationId = post_int('reservation_id');
$idVerified = post_has('id_verified') && $_POST['id_verified'] === '1';

if ($reservationId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid or missing Reservation ID']);
}

if (!$idVerified) {
    json_response(['success' => false, 'message' => 'ID verification is required']);
}

$result = (new Checkout())->handover($reservationId, (int) $user['user_id'], $idVerified);

json_response($result);