<?php
/*
 * Router for the local PHP server, does the same as public/.htaccess on the host.
 * Start:  php -S localhost:8000 -t public router.php
 */

$public = __DIR__ . '/public';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// hidden files (.htaccess, …) are never served
if (preg_match('#(^|/)\.#', $path)) {
    http_response_code(403);
    exit;
}

// existing static files (css, js, images, fonts) are served as they are
if (is_file($public . $path) && !str_ends_with($path, '.php')) {
    return false;
}

// clean URLs, same rules as in .htaccess
if (preg_match('#^/events/?$#', $path)) {
    $script = '/events.php';
} elseif (preg_match('#^/events/([a-z0-9-]+)/?$#', $path, $match)) {
    $_GET['slug'] = $match[1];
    $script = '/event.php';
} elseif (preg_match('#^/kontakt/?$#', $path)) {
    $script = '/kontakt.php';
} else {
    // folders -> their index.php, direct .php URLs stay as they are
    $script = is_dir($public . $path) ? rtrim($path, '/') . '/index.php' : $path;
}

if (!str_ends_with($script, '.php') || !is_file($public . $script)) {
    $script = '/404.php';
}

$_SERVER['SCRIPT_NAME'] = $script;
$_SERVER['SCRIPT_FILENAME'] = $public . $script;
chdir(dirname($public . $script));
require $public . $script;
