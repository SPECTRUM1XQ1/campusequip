<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/User.php";

require_role(['admin']);
$roleFilter = isset($_GET['role']) && $_GET['role'] !== '' ? (string) $_GET['role'] : null;

json_response([
    'success' => true,
    'data'    => (new User())->listAll($roleFilter),
]);
