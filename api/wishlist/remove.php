<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Wishlist.php';

require_post();
$user = require_role(['borrower']);

// Accepts one id (catalog_id) or many (catalog_ids[]).
$ids = post_int_array('catalog_ids');
if (post_int('catalog_id') > 0) {
    $ids[] = post_int('catalog_id');
}

json_response((new Wishlist())->remove((int) $user['user_id'], $ids));
