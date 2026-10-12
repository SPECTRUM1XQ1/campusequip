<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Checkout.php";

require_post();
$user = require_permission("can_confirm_pickup");

$reservationId = post_int('reservation_id'); // Fixed typo here
$idVerified = isset($_POST['id_verified']);

if ($reservationId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid or missing Reservation ID'], 400);
}

if (!$idVerified) {
    json_response(['success' => false, 'message' => 'ID verification is required to process handover'], 400);
}

$result = (new Checkout())->handover($reservationId, (int) $user['user_id'], $idVerified);

json_response($result);