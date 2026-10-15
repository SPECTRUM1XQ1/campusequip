<?php

require_once __DIR__ ."/../helpers.php";
require_once __DIR__ ."/../../src/Equipment.php";

require_post();
$user = require_permission('can_manage_catalog');
// int $catalogId, 
// string $serialNumber, 
// ?string $notes = null, 
// ?string $storageLocation = null

$catalogId = post_int('catalog_id');
$serialNumber = post_string('serial_number');
$notes = post_string_or_null('condition_notes');
$storageLocation = post_string_or_null('storage_location');

if ($catalogId <= 0) {
    json_response(['success' => false,'message'=> 'Catalog_Id Not Found']);
}
if ($serialNumber === '') {
    json_response(['success'=> false,'message'=> 'The serial number is required.']);
}

$result = (new Equipment())->addSerializedUnit($catalogId, $serialNumber,$notes, $storageLocation );

json_response($result);