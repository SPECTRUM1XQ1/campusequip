<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Settings.php';

require_post();
$admin = require_role(['admin']);

// Only the fields the page sent are saved; Settings::save checks every value.
$input = [];
foreach (array_keys(Settings::DEFAULTS) as $key) {
    if (isset($_POST[$key])) {
        $input[$key] = post_string($key);
    }
}

json_response((new Settings())->save($input, (int) $admin['user_id']));
