<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Fine.php';

require_post();
$user = require_permission('can_inspect_returns');

$fineId = post_int('fine_id');

if ($fineId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid or missing Fine ID'], 400);
}

$result = (new Fine())->markPaid($fineId, (int) $user['user_id']);

json_response($result);