<?php

declare(strict_types=1);

use App\Controllers\ArchiveController;
use App\Controllers\AuthController;
use App\Controllers\ImageController;
use App\Controllers\ImportController;
use App\Controllers\ReportController;
use App\Controllers\UserController;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;

/**
 * All application routes are registered here and required once from
 * public/index.php.
 *
 * @var Router $router  injected by public/index.php
 */

$router->get('/api/health', function () {
    return Response::success([
        'status' => 'ok',
        'time'   => date(DATE_ATOM),
    ], 'Service is healthy.');
});

// --- Auth ---------------------------------------------------------------
$router->group('/api/auth', function (Router $router) {
    $router->post('/login', [AuthController::class, 'login']);
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
    $router->get('/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
});

// --- Archives -------------------------------------------------------------
// Any authenticated user can read; only admin/editor can write.
$router->group('/api/archives', function (Router $router) {
    $router->get('', [ArchiveController::class, 'index']);
    $router->get('/{id}', [ArchiveController::class, 'show']);
    $router->get('/{id}/images', [ArchiveController::class, 'images']);

    $router->post('', [ArchiveController::class, 'store'], [
        RoleMiddleware::only([User::ROLE_ADMIN, User::ROLE_EDITOR]),
    ]);
    $router->put('/{id}', [ArchiveController::class, 'update'], [
        RoleMiddleware::only([User::ROLE_ADMIN, User::ROLE_EDITOR]),
    ]);
    $router->delete('/{id}', [ArchiveController::class, 'destroy'], [
        RoleMiddleware::only([User::ROLE_ADMIN]),
    ]);
}, [AuthMiddleware::class]);

// --- Imports (Excel + embedded image extraction) --------------------------
$router->group('/api/imports', function (Router $router) {
    $router->get('', [ImportController::class, 'index']);
    $router->get('/{id}', [ImportController::class, 'show']);
    $router->post('', [ImportController::class, 'store'], [
        RoleMiddleware::only([User::ROLE_ADMIN, User::ROLE_EDITOR]),
    ]);
}, [AuthMiddleware::class]);

// --- Images -----------------------------------------------------------
// 1. Route PUBLIQUE pour que les balises <img> puissent charger l'image
$router->get('/api/images/{id}/raw', [ImageController::class, 'raw']);

// 2. Le reste des routes reste PROTÉGÉ
$router->group('/api/images', function (Router $router) {
    $router->get('/{id}', [ImageController::class, 'show']);
    $router->post('', [ImageController::class, 'store'], [
        RoleMiddleware::only([User::ROLE_ADMIN, User::ROLE_EDITOR]),
    ]);
    $router->delete('/{id}', [ImageController::class, 'destroy'], [
        RoleMiddleware::only([User::ROLE_ADMIN, User::ROLE_EDITOR]),
    ]);
}, [AuthMiddleware::class]);

// --- Users (admin only) ----------------------------------------------------
$router->group('/api/users', function (Router $router) {
    $router->get('', [UserController::class, 'index']);
    $router->get('/{id}', [UserController::class, 'show']);
    $router->post('', [UserController::class, 'store']);
    $router->put('/{id}', [UserController::class, 'update']);
    $router->delete('/{id}', [UserController::class, 'destroy']);
}, [AuthMiddleware::class, RoleMiddleware::only([User::ROLE_ADMIN])]);

// --- Reports ----------------------------------------------------------
$router->group('/api/reports', function (Router $router) {
    $router->get('/summary', [ReportController::class, 'summary']);
    $router->get('/export', [ReportController::class, 'exportCsv']);
    $router->get('/logs', [ReportController::class, 'logs'], [
        RoleMiddleware::only([User::ROLE_ADMIN]),
    ]);
}, [AuthMiddleware::class]);
$router->get('/api/health', [\App\Controllers\HealthController::class, 'check']);