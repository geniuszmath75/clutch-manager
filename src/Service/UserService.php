<?php

declare(strict_types=1);

namespace Src\Service;

use Core\Auth;
use Core\Session;
use RuntimeException;
use InvalidArgumentException;
use Src\Enum\SystemRole;
use Src\Repository\TeamRoleRepository;
use Src\Repository\UserRepository;

final class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TeamRoleRepository $teamRoleRepository,
    )
    {
    }

    /**
     * Updates nickname and/or email for the authenticated user.
     * At least one field must be provided.
     *
     * @return array Updated user data for the response.
     */
    public function updateProfile(array $data): array
    {
        $userId = $this->requireUserId();

        $nickname = isset($data['nickname']) ? trim((string) $data['nickname']) : null;
        $email    = isset($data['email'])    ? trim((string) $data['email'])    : null;
        $teamRoleIdent = isset($data['team_role_ident']) ? trim((string)$data['team_role_ident']) : null;

        if ($nickname === null && $email === null && $teamRoleIdent === null) {
            throw new InvalidArgumentException('At least one field (nickname, email or team_role_ident) is required.', 400);
        }

        if ($nickname !== null) {
            if (mb_strlen($nickname) < 3 || mb_strlen($nickname) > 100) {
                throw new InvalidArgumentException('Nickname must be between 3 and 100 characters.', 400);
            }
            if ($this->userRepository->nicknameExists($nickname)) {
                throw new InvalidArgumentException('Nickname is already taken.', 409);
            }
        }

        if ($email !== null) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email address.', 400);
            }
            if (strlen($email) > 100) {
                throw new InvalidArgumentException('Email is too long.', 400);
            }
            if ($this->userRepository->emailExists($email)) {
                throw new InvalidArgumentException('Email already exists.', 409);
            }
        }

        $teamRoleId = null;

        if ($teamRoleIdent !== null) {
            if (Auth::systemRole() === SystemRole::Coach->value) {
                throw new InvalidArgumentException('Coaches do not have a team role.', 400);
            }

            $teamRoleId = $this->teamRoleRepository->findIdByIdent($teamRoleIdent);

            if (empty($teamRoleId)) {
                throw new RuntimeException('Invalid team role.', 400);
            }
        }

        $user = $this->userRepository->updateProfile($userId, [
            'nickname' => $nickname,
            'email'    => $email,
            'team_role_id' => $teamRoleId,
        ]);

        // Keep session in sync — update only changed fields
        if ($nickname !== null) {
            Session::setUserField('nickname', $user->nickname);
        }
        if ($email !== null) {
            Session::setUserField('email', $user->email);
        }
        if ($teamRoleId !== null) {
            Session::setUserField('team_role', $user->teamRole);
        }

        return [
            'id'       => $user->id,
            'nickname' => $user->nickname,
            'email'    => $user->email,
            'teamRole' => $user->teamRole,
        ];
    }

    /**
     * Changes the authenticated user's password.
     * Requires current password for verification.
     */
    public function updatePassword(array $data): void
    {
        $userId = $this->requireUserId();

        $currentPassword = trim((string) ($data['current_password'] ?? ''));
        $newPassword     = trim((string) ($data['new_password']     ?? ''));
        $confirmPassword = trim((string) ($data['confirm_password'] ?? ''));

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            throw new InvalidArgumentException('All password fields are required.', 400);
        }

        if ($newPassword !== $confirmPassword) {
            throw new InvalidArgumentException('New password and confirmation do not match.', 400);
        }

        if (mb_strlen($newPassword) < 10) {
            throw new InvalidArgumentException('New password must be at least 10 characters.', 400);
        }

        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new InvalidArgumentException('User not found.', 404);
        }

        if (!password_verify($currentPassword, $user->password)) {
            throw new InvalidArgumentException('Current password is invalid.', 400);
        }

        if (password_verify($newPassword, $user->password)) {
            throw new InvalidArgumentException('New password must differ from the current one.', 409);
        }

        $newPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $success = $this->userRepository->updatePassword($userId, $newPassword);
        if (!$success) {
            throw new RuntimeException('Failed to update password.', 500);
        }

        // Regenerate session after password change — keeps user logged in but invalidates old session ID
        Session::regenerate();
    }

    private function requireUserId(): int
    {
        $userId = Auth::userId();

        if ($userId === null) {
            throw new InvalidArgumentException('Authentication failed.', 401);
        }

        return $userId;
    }
}