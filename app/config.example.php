<?php
/*
 * Copy this file to config.php and adjust it.
 * config.php is NOT in git: every environment (local, test, live) has its own.
 */

return [
    // local | test | live
    'env' => 'local',

    // show PHP errors in the browser (never on live)
    'debug' => true,

    // full address of the site, without trailing slash (used for share previews)
    'base_url' => 'http://localhost:8000',

    // SQLite database file (outside of public/, created automatically)
    'db_path' => __DIR__ . '/../data/mainquest.sqlite',

    // uploaded images (folder inside public/ + its URL)
    'uploads_path' => __DIR__ . '/../public/uploads',
    'uploads_url' => '/uploads',

    'timezone' => 'Europe/Berlin',
];
