<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Wishlist.php';

require_post();
$user = require_role(['borrower']);

$catalogId = post_int('catalog_id');
if ($catalogId <= 0) {
    json_response(['success' => false, 'message' => 'Please choose an equipment model.']);
}

// The user id always comes from the session, never from $_POST.
json_response((new Wishlist())->add((int) $user['user_id'], $catalogId));
