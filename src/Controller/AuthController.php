<?php

namespace Src\Controller;

use Core\Auth;
use Core\Database;
use Core\Response;
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
        $pdo = Database::getInstance()->getPDO();
        $this->teamRoleRepository = new TeamRoleRepository($pdo);
        $userRepository = new UserRepository($pdo);
        $systemRoleRepository = new SystemRoleRepository($pdo);
        $this->authService = new AuthService(
            $userRepository,
            $systemRoleRepository,
            $this->teamRoleRepository
        );
    }

    /**
     * GET /auth/login
     */
    public function showLoginView(array $params): void
    {
        if (Auth::isLoggedIn()) {
            Response::redirect('/dashboard');
        }

        Response::view('login.html');
    }

    /**
     * POST /auth/login
     */
    public function loginUser(array $params): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = $this->authService->login($email, $password);

        if (!$result['ok']) {
            Response::redirect('/auth/login?error='.urlencode($result['error']));
        }

        Response::redirect('/dashboard');
    }

    /**
     * GET /auth/register
     */
    public function showRegisterView(array $params): void
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
    public function registerUser(array $params): void
    {
        $nickname = $_POST['nickname'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $systemRoleIdent = $_POST['system_role_ident'] ?? '';
        $teamRoleIdent = $_POST['team_role_ident'] ?? null;

        $result = $this->authService->register(
            $nickname,
            $email,
            $password,
            $systemRoleIdent,
            $teamRoleIdent
        );

        if (!$result['ok']) {
            Response::redirect('/auth/register?error='.urlencode($result['error']));
        }

        Response::redirect('/auth/login?registered=1');
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