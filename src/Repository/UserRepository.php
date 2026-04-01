<?php

declare(strict_types=1);

namespace Src\Repository;

use Core\Database;
use PDO;
use Src\Model\User;

final class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getPDO();
    }

    /**
     * Returns the user by email, or null if it doesn't exist.
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.nickname, u.email, u.password,
                          sr.ident AS system_role,
                          tr.ident AS team_role,
                          u.team_id,
                          u.is_active
                   FROM users u
                   JOIN system_roles sr ON sr.id = u.system_role_id
                   LEFT JOIN team_roles tr ON tr.id = u.team_role_id
                   WHERE u.email = :email'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Checks if the email address is already in use.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Checks if the nickname is already taken.
     */
    public function nicknameExists(string $nickname): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE nickname = :nickname'
        );
        $stmt->execute(['nickname' => $nickname]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Creates a new user. Returns the new record ID.
     *
     * system_role_id — retrieved from the system_roles table by the name 'PLAYER'
     * team_role_id — null upon registration (later assigned by ADMIN/CAPTAIN)
     * team_id — null upon registration
     */
    public function create(string $nickname, string $email, string $password, int $systemRoleId, ?int $teamRoleId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (nickname, email, password, system_role_id, team_role_id)
                   VALUES (:nickname, :email, :password, :system_role_id, :team_role_id)
                   RETURNING id'
        );
        $stmt->execute([
            'nickname' => $nickname,
            'email' => $email,
            'password' => $password,
            'system_role_id' => $systemRoleId,
            'team_role_id' => $teamRoleId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Maps a row from the database to a User object.
     *
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            nickname: $row['nickname'],
            email: $row['email'],
            password: $row['password'],
            systemRole: $row['system_role'],
            teamRole: $row['team_role'] ?? null,
            teamId: isset($row['team_id']) ? (int) $row['team_id'] : null,
            isActive: (bool) $row['is_active'],
        );
    }
}