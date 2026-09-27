<?php
/*
 * Entry point for every PHP page: loads config, helpers, database.
 * Pages in public/ start with:  require __DIR__ . '/../app/bootstrap.php';
 */

declare(strict_types=1);

const APP_PATH = __DIR__;
const PUBLIC_PATH = __DIR__ . '/../public';

$configFile = APP_PATH . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing app/config.php - copy app/config.example.php and adjust it.');
}
$GLOBALS['config'] = require $configFile;

date_default_timezone_set(config('timezone') ?? 'Europe/Berlin');

if (config('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

require APP_PATH . '/helpers.php';
require APP_PATH . '/database.php';
require APP_PATH . '/events.php';
require APP_PATH . '/users.php';
require APP_PATH . '/auth.php';
require APP_PATH . '/mail.php';
require APP_PATH . '/images.php';
require APP_PATH . '/settings.php';
require APP_PATH . '/contact.php';

function config(string $key): mixed
{
    return $GLOBALS['config'][$key] ?? null;
}
