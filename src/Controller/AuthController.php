<?php

namespace Src\Controller;

use Core\Auth;
use Core\Database;
use Core\Response;
use Src\Repository\UserRepository;
use Src\Service\AuthService;

final class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $pdo = Database::getInstance()->getPDO();
        $userRepository = new UserRepository($pdo);
        $this->authService = new AuthService($userRepository);
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

        Response::view('register.html');
    }

    /**
     * POST /auth/register
     */
    public function registerUser(array $params): void
    {
        $nickname = $_POST['nickname'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = $this->authService->register($nickname, $email, $password);

        if (!$result['ok']) {
            Response::redirect('/auth/login?error='.urlencode($result['error']));
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