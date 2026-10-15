<?php

require_once __DIR__."/../helpers.php";
require_once __DIR__."/../../src/Equipment.php";

require_role(['admin', 'staff']);
require_post();
$user = require_permission('can_manage_catalog');

// These keys must match what Equipment::addCatalogModel() reads.
$data = [
    'model_name'     => post_string('model_name'),
    'category_id'    => post_int('category_id'),
    'max_loan_days'  => post_int('max_loan_days', 3),
    'late_fine_rate' => post_float('late_fine_rate', 5.00),
    'description'    => post_string_or_null('description'),
];

$result = (new Equipment())->addCatalogModel($data);

json_response($result);