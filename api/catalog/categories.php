<?php
require_once __DIR__ ."/../helpers.php";
require_once __DIR__ ."/../../src/Equipment.php";

current_user(); // signed-in users only (the catalog is not public)

json_response(['success' => true,'data'=> (new Equipment())->listCategories()]);