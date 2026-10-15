<?php
require_once __DIR__ ."/../helpers.php";
require_once __DIR__ ."/../../src/Equipment.php";

json_response(['success' => true,'data'=> (new Equipment())->listCategories()]);