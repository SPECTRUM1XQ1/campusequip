<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/User.php";

require_post();
require_role(['admin']);

$userId  = post_int('user_id');
$newRole = post_string('role');

if ($userId <= 0) {
    json_response(['success' => false, 'message' => 'The user was not found.']);
}
if (!in_array($newRole, ['admin', 'staff', 'borrower'], true)) {
    json_response(['success' => false, 'message' => 'Invalid role. Use admin, staff or borrower.']);
}

json_response((new User())->updateRole($userId, $newRole));
