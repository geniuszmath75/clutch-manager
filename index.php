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
$router->get('/dashboard/stats', [Src\Controller\DashboardController::class, 'getDashboardStats']);
$router->get('/dashboard/admin/teams', [Src\Controller\DashboardController::class, 'getAdminTeamStats']);
$router->get('/dashboard/admin/logs', [Src\Controller\DashboardController::class, 'getAdminAuditLog']);
$router->get('/dashboard/players', [Src\Controller\DashboardController::class, 'showPlayersView']);
$router->get('/dashboard/matches', [Src\Controller\DashboardController::class, 'showMatchesView']);
$router->get('/dashboard/matches/{id}', [Src\Controller\DashboardController::class, 'showMatchDetailsView']);
$router->get('/dashboard/strategies', [Src\Controller\DashboardController::class, 'showStrategiesView']);
$router->get('/dashboard/strategies/{id}', [Src\Controller\DashboardController::class, 'showStrategyDetailsView']);
$router->get('/dashboard/settings', [Src\Controller\DashboardController::class, 'showSettingsView']);

// --- Players ---
$router->get('/players', [Src\Controller\PlayerController::class, 'getPlayers']);
$router->get('/players/available', [Src\Controller\PlayerController::class, 'getAvailablePlayers']);
$router->post('/players/{id}/team', [Src\Controller\PlayerController::class, 'addPlayerToTeam']);
$router->delete('/players/{id}/team', [Src\Controller\PlayerController::class, 'removePlayerFromTeam']);
$router->put('/players/{id}', [Src\Controller\PlayerController::class, 'updatePlayer']);
$router->patch('/players/{id}/deactivate', [Src\Controller\PlayerController::class, 'deactivatePlayer']);
$router->patch('/players/{id}/activate', [Src\Controller\PlayerController::class, 'activatePlayer']);
$router->delete('/players/{id}', [Src\Controller\PlayerController::class, 'deletePlayer']);

// --- Matches ---
$router->get('/matches', [Src\Controller\GameMatchController::class, "getMatches"]);
$router->get('/matches/{id}', [Src\Controller\GameMatchController::class, "getMatchDetails"]);
$router->post('/matches', [Src\Controller\GameMatchController::class, "createMatch"]);
$router->put('/matches/{id}', [Src\Controller\GameMatchController::class, "updateMatch"]);
$router->delete('/matches/{id}', [Src\Controller\GameMatchController::class, "deleteMatch"]);

// --- Game maps ---
$router->get('/game-maps', [Src\Controller\GameMapController::class, "getGameMaps"]);

// --- Game modes ---
$router->get('/game-modes', [Src\Controller\GameModeController::class, "getGameModes"]);

// --- Strategies ---
$router->get('/strategies', [Src\Controller\StrategyController::class, "getStrategies"]);
$router->get('/strategies/{id}', [Src\Controller\StrategyController::class, "getStrategyDetails"]);
$router->post('/strategies', [Src\Controller\StrategyController::class, "createStrategy"]);
$router->put('/strategies/{id}', [Src\Controller\StrategyController::class, "updateStrategy"]);
$router->delete('/strategies/{id}', [Src\Controller\StrategyController::class, "deleteStrategy"]);

// --- Strategy types ---
$router->get('/strategy-types', [Src\Controller\StrategyTypeController::class, "getStrategyTypes"]);

// --- Team ---
$router->get('/teams', [Src\Controller\TeamController::class, "getTeams"]);
$router->post('/teams', [Src\Controller\TeamController::class, "createTeam"]);

// --- User profile
$router->patch('/users/me', [Src\Controller\UserController::class, "updateUserProfile"]);
$router->patch('/users/me/password', [Src\Controller\UserController::class, "updateUserPassword"]);

// 7. Dispatch

$router->dispatch();