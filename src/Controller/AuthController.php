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

final class AuthController
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
     * POST /auth/login
     */
    public function loginUser(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        try {
            $this->authService->login($email, $password);
            Response::redirect('/dashboard');
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError('/auth/login', $e->getCode(), $e->getMessage());
        }
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

    /**
     * POST /auth/register
     */
    public function registerUser(): void
    {
        $nickname = $_POST['nickname'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $systemRoleIdent = $_POST['system_role_ident'] ?? '';
        $teamRoleIdent = $_POST['team_role_ident'] ?? null;

        try {
            $this->authService->register(
                $nickname,
                $email,
                $password,
                $systemRoleIdent,
                $teamRoleIdent
            );

            Response::redirect('/auth/login?registered=1');
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->handleError('/auth/register', $e->getCode(), $e->getMessage());
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

    /**
     * Redirects to the given path with an error message,
     * or returns JSON for AJAX requests.
     */
    private function handleError(string $redirectPath, int $code, string $message): void
    {
        if (Auth::isAjaxRequest()) {
            Response::error($code, $message);
            return;
        }

        Response::redirect($redirectPath . '?error=' . urlencode($message));
    }
}