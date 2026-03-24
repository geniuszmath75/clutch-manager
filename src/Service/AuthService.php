<?php

declare(strict_types=1);

namespace Src\Service;

use Core\Session;
use Src\Repository\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * Logs the user in.
     *
     * @return array{ok: bool, error?: string}
     */
    public function login(string $email, string $password): array
    {
        $email = trim($email);
        $password = trim($password);

        if (empty($email) || empty($password)) {
            return ['ok' => false, 'error' => 'Email and password are required.'];
        }

        $user = $this->userRepository->findByEmail($email);

        if (empty($user) || !password_verify($password, $user->password)) {
            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        if (!$user->isActive) {
            return ['ok' => false, 'error' => 'Account is inactive.'];
        }

        Session::regenerate();

        Session::set('user', [
            'id' => $user->id,
            'nickname' => $user->nickname,
            'email' => $user->email,
            'system_role' => $user->systemRole,
            'team_id' => $user->teamId,
        ]);

        return ['ok' => true];
    }

    public function logout(): void
    {
        Session::destroy();
    }

    /**
     * Registers a new user with the PLAYER role.
     *
     * @return array{ok: bool, error?: string}
     */
    public function register(string $nickname, string $email, string $password): array
    {
        $nickname = trim($nickname);
        $email = trim($email);
        $password = trim($password);

        $validationError = $this->validateRegistrationInput($nickname, $email, $password);
        if (!empty($validationError)) {
            return ['ok' => false, 'error' => $validationError];
        }

        if ($this->userRepository->emailExists($email)) {
            return ['ok' => false, 'error' => 'Email already exists.'];
        }

        if ($this->userRepository->nicknameExists($nickname)) {
            return ['ok' => false, 'error' => 'Nickname is already taken.'];
        }

        $password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepository->create($nickname, $email, $password);

        return ['ok' => true];
    }

    /**
     * Validates registration data. Returns an error message or null if OK.
     */
    private function validateRegistrationInput(string $nickname, string $email, string $password): ?string
    {
        if (empty($nickname) || empty($email) || empty($password)) {
            return 'All fields are required.';
        }

        if (strlen($nickname) < 3 || strlen($nickname) > 255) {
            return 'Nickname must be between 3 and 255 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format.';
        }

        if (strlen($email) > 255) {
            return 'Email is too long.';
        }

        if (strlen($password) < 10) {
            return 'Password must be at least 10 characters.';
        }

        return null;
    }
}