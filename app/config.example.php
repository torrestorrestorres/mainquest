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

    // one-time secret for creating the first admin: /admin/setup.php?token=…
    // (only works while there is no user yet). Use a long random string.
    'setup_token' => '',

    // secret for signing (spam protection of the contact form). Long random string, never share.
    'app_secret' => '',

    // logout after this many hours without activity
    'session_idle_hours' => 8,

    // e-mails (invites, password reset). Disabled = written to data/mail.log
    'mail' => [
        'enabled' => false,
        'from' => 'noreply@example.com',   // must be an address of your own domain
        'from_name' => 'MainQuest',
        'reply_to' => 'info@example.com',
    ],
];
