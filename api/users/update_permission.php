<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/User.php";

require_post();
$admin = require_role(['admin']);

$userId = post_int('user_id');

// One radio group named "job": each staff member does exactly one job.
$jobs = ['can_approve_requests', 'can_inspect_returns', 'can_confirm_pickup', 'can_manage_catalog'];
$job  = post_string('job');

if ($userId <= 0) {
    json_response(['success' => false, 'message' => 'User ID not found.']);
}
if (!in_array($job, $jobs, true)) {
    json_response(['success' => false, 'message' => 'Please choose one job for this staff member.']);
}

// Chosen job = 1, every other job = 0.
$permissions = [];
foreach ($jobs as $flag) {
    $permissions[$flag] = ($flag === $job) ? 1 : 0;
}

json_response((new User())->updateStaffPermissions($userId, $permissions, (int) $admin['user_id']));
