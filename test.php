<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Reservation.php';

$reservation = new Reservation();

try {
    $result = $reservation->create(1, 1, '2026-10-01', '2026-10-05', 'Test run');
    print_r($result);
} catch (PDOException $e) {
    echo "DATABASE ERROR: " . $e->getMessage() . "\n";
}