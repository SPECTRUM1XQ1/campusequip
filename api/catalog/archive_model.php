<?php
// POST catalog_id=..&archived=1  -> archive    |    archived=0 -> restore
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../../src/Equipment.php";

require_post();
require_permission('can_manage_catalog');

$catalogId = post_int('catalog_id');
$flag      = post_string('archived');

if ($catalogId <= 0) {
    json_response(['success' => false, 'message' => 'Catalog ID is required.']);
}
if ($flag !== '0' && $flag !== '1') {
    json_response(['success' => false, 'message' => 'archived must be 1 (archive) or 0 (restore).']);
}

json_response((new Equipment())->setCatalogArchived($catalogId, $flag === '1'));
