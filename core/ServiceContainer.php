<?php

namespace Core;

use Src\Repository\DashboardRepository;
use Src\Repository\GameMapRepository;
use Src\Repository\GameMatchRepository;
use Src\Repository\GameModeRepository;
use Src\Repository\PlayerRepository;
use Src\Repository\StrategyRepository;
use Src\Repository\StrategyTypeRepository;
use Src\Repository\SystemRoleRepository;
use Src\Repository\TeamRepository;
use Src\Repository\TeamRoleRepository;
use Src\Repository\UserRepository;
use Src\Service\AuthService;
use Src\Service\DashboardService;
use Src\Service\GameMapService;
use Src\Service\GameMatchService;
use Src\Service\GameModeService;
use Src\Service\PlayerService;
use Src\Service\StrategyService;
use Src\Service\StrategyTypeService;
use Src\Service\TeamService;
use Src\Service\UserService;

/**
 * ServiceContainer — provides singleton instances of services and repositories.
 *
 * Usage:
 *   $authService = ServiceContainer::getAuthService();
 *   $userService = ServiceContainer::getUserService();
 *   $teamService = ServiceContainer::getTeamService();
 *   $dashboardService = ServiceContainer::getDashboardService();
 *   etc...
 *
 * All instances are singletons — only one instance per type exists in the application.
 */
final class ServiceContainer
{
    /**
     * Singleton instances registry — Repositories
     */
    private static ?UserRepository $userRepository = null;
    private static ?SystemRoleRepository $systemRoleRepository = null;
    private static ?TeamRoleRepository $teamRoleRepository = null;
    private static ?TeamRepository $teamRepository = null;
    private static ?DashboardRepository $dashboardRepository = null;
    private static ?GameMapRepository $gameMapRepository = null;
    private static ?GameModeRepository $gameModeRepository = null;
    private static ?StrategyTypeRepository $strategyTypeRepository = null;
    private static ?PlayerRepository $playerRepository = null;
    private static ?GameMatchRepository $gameMatchRepository = null;
    private static ?StrategyRepository $strategyRepository = null;

    /**
     * Singleton instances registry — Services
     */
    private static ?AuthService $authService = null;
    private static ?UserService $userService = null;
    private static ?TeamService $teamService = null;
    private static ?DashboardService $dashboardService = null;
    private static ?GameMapService $gameMapService = null;
    private static ?GameModeService $gameModeService = null;
    private static ?StrategyTypeService $strategyTypeService = null;
    private static ?GameMatchService $gameMatchService = null;
    private static ?PlayerService $playerService = null;
    private static ?StrategyService $strategyService = null;

    /**
     * READ
     */

    public static function getUserRepository(): UserRepository
    {
        return self::$userRepository ??= new UserRepository();
    }

    public static function getSystemRoleRepository(): SystemRoleRepository
    {
        return self::$systemRoleRepository ??= new SystemRoleRepository();
    }

    public static function getTeamRoleRepository(): TeamRoleRepository
    {
        return self::$teamRoleRepository ??= new TeamRoleRepository();
    }

    public static function getTeamRepository(): TeamRepository
    {
        return self::$teamRepository ??= new TeamRepository();
    }

    public static function getDashboardRepository(): DashboardRepository
    {
        return self::$dashboardRepository ??= new DashboardRepository();
    }

    public static function getGameMapRepository(): GameMapRepository
    {
        return self::$gameMapRepository ??= new GameMapRepository();
    }

    public static function getGameModeRepository(): GameModeRepository
    {
        return self::$gameModeRepository ??= new GameModeRepository();
    }

    public static function getStrategyTypeRepository(): StrategyTypeRepository
    {
        return self::$strategyTypeRepository ??= new StrategyTypeRepository();
    }

    public static function getPlayerRepository(): PlayerRepository
    {
        return self::$playerRepository ??= new PlayerRepository();
    }

    public static function getGameMatchRepository(): GameMatchRepository
    {
        return self::$gameMatchRepository ??= new GameMatchRepository();
    }

    public static function getStrategyRepository(): StrategyRepository
    {
        return self::$strategyRepository ??= new StrategyRepository(
            self::getPlayerRepository()
        );
    }

    /**
     * SERVICES
     */

    public static function getAuthService(): AuthService
    {
        return self::$authService ??= new AuthService(
            self::getUserRepository(),
            self::getSystemRoleRepository(),
            self::getTeamRoleRepository()
        );
    }

    public static function getUserService(): UserService
    {
        return self::$userService ??= new UserService(
            self::getUserRepository(),
            self::getTeamRoleRepository()
        );
    }

    public static function getTeamService(): TeamService
    {
        return self::$teamService ??= new TeamService(
            self::getTeamRepository(),
            self::getUserRepository()
        );
    }

    public static function getDashboardService(): DashboardService
    {
        return self::$dashboardService ??= new DashboardService(
            self::getDashboardRepository()
        );
    }

    public static function getGameMapService(): GameMapService
    {
        return self::$gameMapService ??= new GameMapService(
            self::getGameMapRepository()
        );
    }

    public static function getGameModeService(): GameModeService
    {
        return self::$gameModeService ??= new GameModeService(
            self::getGameModeRepository()
        );
    }

    public static function getStrategyTypeService(): StrategyTypeService
    {
        return self::$strategyTypeService ??= new StrategyTypeService(
            self::getStrategyTypeRepository()
        );
    }

    public static function getGameMatchService(): GameMatchService
    {
        return self::$gameMatchService ??= new GameMatchService(
            self::getGameMatchRepository(),
            self::getPlayerRepository()
        );
    }

    public static function getPlayerService(): PlayerService
    {
        return self::$playerService ??= new PlayerService(
            self::getPlayerRepository(),
            self::getTeamRoleRepository(),
            self::getSystemRoleRepository()
        );
    }

    public static function getStrategyService(): StrategyService
    {
        return self::$strategyService ??= new StrategyService(
            self::getStrategyRepository()
        );
    }

    /**
     * Prevent instantiation — ServiceContainer is static only
     */
    private function __construct()
    {
    }
}

