<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Fine.php';

require_post();
$user = require_role(['admin']);

$fineId = post_int('fine_id');
$reason = post_string('reason');

if ($fineId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid or missing Fine ID'], 400);
}

if ($reason === '') {
    json_response(['success' => false, 'message' => 'A reason is required to waive a fine.'], 400);
}

$result = (new Fine())->waive($fineId, (int) $user['user_id'], $reason);

json_response($result);