<?php

declare(strict_types=1);

use App\Config\Env;
use App\Core\ExceptionHandler;
use App\Core\Request;
use App\Core\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

Env::load();
date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'UTC'));

ExceptionHandler::register();


header('Access-Control-Allow-Origin: ' . (string) Env::get('CORS_ALLOWED_ORIGINS', '*'));
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


$request = Request::capture();
$router = new Router();

require __DIR__ . '/../app/Config/Routes.php';

$response = $router->dispatch($request);
$response->send();