<?php
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Equipment.php";

require_role(['admin', 'staff']);
require_post();
require_permission('can_manage_catalog');

json_response((new Equipment())->addCategory(post_string('category_name')));
