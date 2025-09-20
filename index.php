<?php
ini_set('memory_limit', '512M');

$_ENV['APP_ENV'] = 'prod';
$_ENV['APP_SECRET'] = 'ThisIsAVerySecureSecretKeyForProduction123456789';
$_ENV['DATABASE_URL'] = 'sqlite:///' . __DIR__ . '/var/data.db';

require_once __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$kernel = new Kernel('prod', false);
$request = Request::createFromGlobals();

// If accessing root, change to simple-login
if ($request->getPathInfo() === '/') {
    $request = Request::create('/simple-login', $request->getMethod(), $request->request->all());
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
