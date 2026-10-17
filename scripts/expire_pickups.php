<?php
/**
 * Cancels approved reservations whose pick-up window has ended (frees the unit and logs it as "System").
 * Run it on a schedule so expiry does not depend on someone opening a page:
 *
 *   Linux cron (every 15 minutes):
 *     0,15,30,45 * * * *  php /path/to/campusequip/scripts/expire_pickups.php
 *   Windows XAMPP: Task Scheduler -> run  C:\xampp\php\php.exe  with the argument
 *     C:\xampp\htdocs\campusequip\scripts\expire_pickups.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Reservation.php';

$expired = (new Reservation())->expireLapsedPickups();
echo date('Y-m-d H:i:s') . " - expired {$expired} reservation(s).\n";
