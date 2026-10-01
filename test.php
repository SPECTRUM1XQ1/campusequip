<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Reservation.php';
require_once __DIR__ . '/src/Equipment.php';

$reservation = new Reservation();

try {
    $create = $reservation->create(1, 1, '2026-10-01', '2026-10-05', 'Test run');
    print_r($create);

    $listForBorrower = $reservation->listForBorrower(12);
    print_r($listForBorrower);

    $listPending = $reservation->listPending();
    print_r($listPending);
    
    $countPending = $reservation->countPending();
    print_r($countPending);

    $approve = $reservation->approve(1, 1, 1);
    print($approve);
    
    $decline  = $reservation->decline(1, 12, "");
    print_r($decline);

    $cancel = $reservation->cancel(1, 12);
    print_r($cancel);
} catch (PDOException $e) {
    echo "DATABASE ERROR: " . $e->getMessage() . "\n";
}