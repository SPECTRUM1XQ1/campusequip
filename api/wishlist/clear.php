<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Wishlist.php';

require_post();
$user = require_role(['borrower']);

json_response((new Wishlist())->clear((int) $user['user_id']));
