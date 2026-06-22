<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$_SERVER['HTTP_ACCEPT'] = 'application/json';

$basePath = __DIR__.'/../../backend';

// Laravel owns the /api prefix. Do not let Symfony remove it as a script path.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
unset(
    $_SERVER['PATH_INFO'],
    $_SERVER['ORIG_PATH_INFO'],
    $_SERVER['ORIG_SCRIPT_NAME'],
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
