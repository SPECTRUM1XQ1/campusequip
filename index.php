<?php
// Typing http://localhost/campusequip/ opens the public landing page (or the dashboard if already signed in).
define('APP_INIT', true);
require_once __DIR__ . '/config/config.php';
header('Location: ' . APP_URL . '/index.php');
exit;
