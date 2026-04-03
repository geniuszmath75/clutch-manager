<?php

declare(strict_types=1);

/**
 * index.php — application entry point (front controller).
 *
 * Bootstrap order:
 * 1. Environment constants (BASE_PATH)
 * 2. Loading .env variables
 * 3. Class autoloader
 * 4. Session start
 * 5. Route registration
 * 6. Dispatch requests
 */

// 1. CONSTANTS

const BASE_PATH = __DIR__;

// 2. ENV VARIABLES
// Set by docker-compose
// Accessible via $_ENV

// 3. Autoloader

require_once BASE_PATH . '/autoload.php';

// 4. Session

use Core\Response;
use Core\Router;
use Core\Session;

Session::start();

// 5. Error handling

set_exception_handler(function (Throwable $e): void {
    error_log(sprintf(
        '[ERROR]: Unhandled exception: %s in %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    Response::serverError("Ooops! Server failed the clutch. Please try refreshing the page in a few minutes.");
});

// 6. Router

$router = new Router();

// --- Auth ---
$router->get('/auth/login', [Src\Controller\AuthController::class, 'showLoginView']);
$router->post('/auth/login', [Src\Controller\AuthController::class, 'loginUser']);
$router->get('/auth/register', [Src\Controller\AuthController::class, 'showRegisterView']);
$router->post('/auth/register', [Src\Controller\AuthController::class, 'registerUser']);
$router->post('/auth/logout', [Src\Controller\AuthController::class, 'logoutUser']);

// --- Dashboard ---
$router->get('/', [Src\Controller\DashboardController::class, 'showDashboardView']);
$router->get('/dashboard', [Src\Controller\DashboardController::class, 'showDashboardView']);
$router->get('/dashboard/players', [Src\Controller\DashboardController::class, 'showPlayersView']);

// --- Players ---
$router->get('/players', [Src\Controller\PlayerController::class, 'getPlayers']);
$router->put('/players/{id}', [Src\Controller\PlayerController::class, 'updatePlayer']);
$router->patch('/players/{id}', [Src\Controller\PlayerController::class, 'deactivatePlayer']);

// 7. Dispatch

$router->dispatch();