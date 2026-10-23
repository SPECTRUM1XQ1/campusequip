<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Notification.php';

// GET: the signed-in user's notification feed (see src/Notification.php).
header('Cache-Control: no-store');
$user = current_user();

json_response(['success' => true, 'items' => (new Notification())->forUser($user)]);
