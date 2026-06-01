<?php

namespace Src\Controller;

use Core\Auth;
use Core\Response;
use InvalidArgumentException;
use RuntimeException;
use Src\Repository\SystemRoleRepository;
use Src\Repository\TeamRoleRepository;
use Src\Repository\UserRepository;
use Src\Service\AuthService;

final class AuthController extends BaseController
{
    private AuthService $authService;
    private TeamRoleRepository $teamRoleRepository;

    public function __construct()
    {
        $this->teamRoleRepository = new TeamRoleRepository();
        $userRepository = new UserRepository();
        $systemRoleRepository = new SystemRoleRepository();
        $this->authService = new AuthService(
            $userRepository,
            $systemRoleRepository,
            $this->teamRoleRepository
        );
    }

    // -------------------------------------------------------------------------
    // Views (GET)
    // -------------------------------------------------------------------------

    /**
     * GET /auth/login
     */
    public function showLoginView(): void
    {
        if (Auth::isLoggedIn()) {
            Response::redirect('/dashboard');
        }

        Response::view('login.html');
    }

    /**
     * GET /auth/register
     */
    public function showRegisterView(): void
    {
        if (Auth::isLoggedIn()) {
            Response::redirect('/dashboard');
        }

        $teamRoles = $this->teamRoleRepository->findAll();

        Response::view('register.php', ['teamRoles' => $teamRoles]);
    }

    // -------------------------------------------------------------------------
    // API endpoints
    // -------------------------------------------------------------------------

    /**
     * POST /auth/login
     */
    public function loginUser(): void
    {
        try {
            $body = $this->parseJsonBody();
            $email = $body['email'] ?? '';
            $password = $body['password'] ?? '';

            $this->authService->login($email, $password);

            Response::json([
                'success' => true,
                'message' => 'Logged in successfully'
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * POST /auth/register
     */
    public function registerUser(): void
    {
        try {
            $body = $this->parseJsonBody();
            $nickname = $body['nickname'] ?? '';
            $email = $body['email'] ?? '';
            $password = $body['password'] ?? '';
            $systemRoleIdent = $body['system_role_ident'] ?? '';
            $teamRoleIdent = $body['team_role_ident'] ?? null;

            $this->authService->register(
                $nickname,
                $email,
                $password,
                $systemRoleIdent,
                $teamRoleIdent
            );

            Response::json([
                'success' => true,
                'message' => 'Registered successfully'
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }

    }

    /**
     * POST /auth/logout
     */
    public function logoutUser(): void
    {
        $this->authService->logout();
        Response::redirect('/auth/login');
    }
}