<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$_SERVER['HTTP_ACCEPT'] = 'application/json';

$basePath = __DIR__.'/../../backend';

header('X-IAIOI-API-Front: 2026-06-22.2');

// Force a root front controller even when Hostinger appends PATH_INFO to it.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $basePath.'/public/index.php';
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
unset(
    $_SERVER['PATH_INFO'],
    $_SERVER['ORIG_PATH_INFO'],
    $_SERVER['ORIG_SCRIPT_NAME'],
    $_SERVER['REDIRECT_SCRIPT_URL'],
    $_SERVER['REDIRECT_SCRIPT_URI'],
);

if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath.'/vendor/autoload.php';

$app = require_once $basePath.'/bootstrap/app.php';

if (method_exists($app, 'usePublicPath')) {
    $app->usePublicPath(dirname(__DIR__));
}

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
