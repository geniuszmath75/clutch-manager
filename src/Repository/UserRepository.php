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
     * READ
     */

    /**
     * Returns the user by id, or null if it doesn't exist.
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.nickname, u.email, u.password,
                   sr.ident AS system_role,
                   tr.ident AS team_role,
                   u.team_id,
                   u.is_active,
                   t.name AS team_name,
                   u.created_at,
                   u.updated_at
            FROM users u
            JOIN system_roles sr ON sr.id = u.system_role_id
            LEFT JOIN team_roles tr ON tr.id = u.team_role_id
            LEFT JOIN teams t ON t.id = u.team_id
            WHERE u.id = :id
              AND u.deleted_at IS NULL
        ");
        $params = ['id' => $id];
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? User::fromRow($row) : null;
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
                          u.is_active,
                          t.name AS team_name,
                          u.created_at,
                          u.updated_at
                   FROM users u
                   JOIN system_roles sr ON sr.id = u.system_role_id
                   LEFT JOIN team_roles tr ON tr.id = u.team_role_id
                   LEFT JOIN teams t ON t.id = u.team_id
                   WHERE u.email = :email 
                        AND u.deleted_at IS NULL
        ');
        $params = [':email' => $email];
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Checks if the email address is already in use.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE email = :email');
        $params = [':email' => $email];
        $stmt->execute($params);
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
        $params = [':nickname' => $nickname];
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * CREATE
     */

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
        $params = [
            ':nickname' => $nickname,
            ':email' => $email,
            ':password' => $password,
            ':system_role_id' => $systemRoleId,
            ':team_role_id' => $teamRoleId
        ];
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * UPDATE
     */

    /**
     * Updates nickname and/or email for a given user.
     * Returns the updated User model.
     */
    public function updateProfile(int $id, array $data): User
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET nickname   = COALESCE(:nickname, nickname),
                email      = COALESCE(:email, email),
                team_role_id = COALESCE(:team_role_id, team_role_id),
                updated_at = NOW()
            WHERE id = :id
              AND deleted_at IS NULL
        ");
        $stmt->execute([
            ':nickname' => $data['nickname'] ?? null,
            ':email'    => $data['email']    ?? null,
            ':team_role_id' => $data['team_role_id'] ?? null,
            ':id'       => $id,
        ]);

        return $this->findById($id);
    }

    /**
     * Updates the hashed password for a given user and regenerates the session.
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET password = :password_hash,
                updated_at    = NOW()
            WHERE id = :id
              AND deleted_at IS NULL
        ");
        $stmt->execute([
            ':password_hash' => $hashedPassword,
            ':id'            => $id,
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Assigns a team to a user and updates the session-visible team_id.
     * Used after COACH creates a new team.
     */
    public function assignTeam(int $userId, int $teamId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET team_id    = :team_id,
                updated_at = NOW()
            WHERE id = :id
              AND deleted_at IS NULL
        ");
        $params = [':team_id' => $teamId, ':id' => $userId];
        $stmt->execute($params);

        return $stmt->rowCount() === 1;
    }
}