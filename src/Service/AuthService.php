<?php

declare(strict_types=1);

namespace Src\Service;

use Core\Session;
use Src\Enum\SystemRole;
use Src\Enum\TeamRole;
use Src\Repository\SystemRoleRepository;
use Src\Repository\TeamRoleRepository;
use Src\Repository\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SystemRoleRepository $systemRoleRepository,
        private readonly TeamRoleRepository $teamRoleRepository,
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
     * Registers a new PLAYER or COACH.
     *
     * PLAYER must provide a teamRoleIdent (e.g. 'IGL', 'ENTRY').
     * COACH does not have a team role — teamRoleIdent must be null.
     *
     * @return array{ok: bool, error?: string}
     */
    public function register(
        string $nickname,
        string $email,
        string $password,
        string $systemRoleIdent,
        ?string $teamRoleIdent,
    ): array
    {
        $nickname = trim($nickname);
        $email = trim($email);
        $password = trim($password);
        $systemRoleIdent = trim($systemRoleIdent);

        $validationError = $this->validateRegistrationInput($nickname, $email, $password, $systemRoleIdent, $teamRoleIdent);
        if (!empty($validationError)) {
            return ['ok' => false, 'error' => $validationError];
        }

        if ($this->userRepository->emailExists($email)) {
            return ['ok' => false, 'error' => 'Email already exists.'];
        }

        if ($this->userRepository->nicknameExists($nickname)) {
            return ['ok' => false, 'error' => 'Nickname is already taken.'];
        }

        $systemRoleId = $this->systemRoleRepository->findIdByIdent($systemRoleIdent);

        if (empty($systemRoleId)) {
            return ['ok' => false, 'error' => 'Invalid system role.'];
        }

        $teamRoleId = null;

        if (!empty($teamRoleIdent)) {
            $teamRoleId = $this->teamRoleRepository->findIdByIdent($teamRoleIdent);

            if (empty($teamRoleId)) {
                return ['ok' => false, 'error' => 'Invalid team role.'];
            }
        }

        $password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepository->create($nickname, $email, $password, $systemRoleId, $teamRoleId);

        return ['ok' => true];
    }

    /**
     * Validates registration data. Returns an error message or null if OK.
     */
    private function validateRegistrationInput(
        string $nickname,
        string $email,
        string $password,
        string $systemRoleIdent,
        ?string $teamRoleIdent
    ): ?string
    {
        if (empty($nickname) || empty($email) || empty($password) || empty($systemRoleIdent)) {
            return 'All fields are required.';
        }

        if (strlen($nickname) < 3 || strlen($nickname) > 100) {
            return 'Nickname must be between 3 and 100 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format.';
        }

        if (strlen($email) > 100) {
            return 'Email is too long.';
        }

        if (strlen($password) < 10) {
            return 'Password must be at least 10 characters.';
        }

        $registrableRoles = [SystemRole::Player->value, SystemRole::Coach->value];

        if (!in_array($systemRoleIdent, $registrableRoles, strict: true)) {
            return 'Invalid system role.';
        }

        // PLAYER must pick a team role; COACH must not
        if ($systemRoleIdent === SystemRole::Player->value && empty($teamRoleIdent)) {
            return 'Team role is required for players.';
        }

        if ($systemRoleIdent === SystemRole::Coach->value && !empty($teamRoleIdent)) {
            return 'Coaches do not have a team role.';
        }

        $validTeamRoles = array_column(TeamRole::cases(), 'value');
        if (!empty($teamRoleIdent) && !in_array($teamRoleIdent, $validTeamRoles, strict: true)) {
            return 'Invalid team role.';
        }

        return null;
    }
}