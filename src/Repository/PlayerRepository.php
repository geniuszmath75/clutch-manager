<?php

namespace Src\Repository;

use Core\Database;
use PDO;
use Src\Enum\SystemRole;
use Src\Model\Player;

final class PlayerRepository
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
     * Returns all active players with the PLAYER role, along with the team role name.
     *
     * @return Player[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                u.id,
                u.nickname,
                u.email,
                sr.ident AS system_role_ident,
                tr.ident AS team_role_ident,
                u.is_active
            FROM users u
            JOIN system_roles sr ON sr.id = u.system_role_id
            LEFT JOIN team_roles tr ON tr.id = u.team_role_id
            WHERE sr.ident = :player_role AND u.deleted_at IS NULL
            ORDER BY tr.ident ASC
        ");

        $params = ['player_role' => SystemRole::Player->value];

        $stmt->execute($params);

        return array_map(
            fn(array $row) => Player::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Returns the player by ID, or null if it doesn't exist/is not a PLAYER.
     */
    public function findById(int $id): ?Player
    {
        $stmt = $this->pdo->prepare("
            SELECT
                u.id,
                u.nickname,
                u.email,
                sr.ident AS system_role_ident,
                tr.ident AS team_role_ident,
                u.is_active
            FROM users u
            JOIN system_roles sr ON sr.id = u.system_role_id
            LEFT JOIN team_roles tr ON tr.id = u.team_role_id
            WHERE sr.ident = :player_role AND u.id = :id AND u.deleted_at IS NULL
        ");

        $params = ['player_role' => SystemRole::Player->value, 'id' => $id];

        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Player::fromRow($row) : null;
    }

    /**
     * Returns players with the specified team role.
     *
     * @return Player[]
     */
    public function findByTeamRole(string $teamRoleIdent): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                u.id,
                u.nickname,
                u.email,
                tr.ident AS team_role_ident,
                u.is_active
            FROM users u
            JOIN system_roles sr ON sr.id = u.system_role_id
            JOIN team_roles tr ON tr.id = u.team_role_id
            WHERE sr.ident = :player_role AND tr.ident = :team_role_ident AND u.deleted_at IS NULL
            ORDER BY tr.ident ASC
        ");

        $params = [':player_role' => SystemRole::Player->value, ':team_role_ident' => $teamRoleIdent];

        $stmt->execute($params);

        return array_map(
            fn(array $row) => Player::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * UPDATE
     */

    /**
     * Updates a player's nickname and/or team_role_id.
     * Accepts validated data from PlayerService.
     *
     * @param array{nickname?: string, team_role_id?: int|null} $data
     */
    public function update(int $id, array $data): bool
    {
        $sets = [];
        $params = [':id' => $id];

        if (array_key_exists('nickname', $data)) {
            $sets[] = 'nickname = :nickname';
            $params[':nickname'] = $data['nickname'];
        }

        if (array_key_exists('team_role_id', $data)) {
            $sets[] = 'team_role_id = :team_role_id';
            $params[':team_role_id'] = $data['team_role_id'];
        }

        if (empty($sets)) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() === 1;
    }

    public function deactivate(int $id, int $systemRoleId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET is_active = false
            WHERE id = :id
                AND system_role_id = :system_role_id
        ");

        $params = [':id' => $id, ':system_role_id' => $systemRoleId];

        $stmt->execute($params);

        return $stmt->rowCount() === 1;
    }

    /**
     * Checks if the given nickname is already taken (optionally excluding the player with the given ID).
     */
    public function nicknameExists(string $nickname, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE nickname = :nickname';
        $params = [':nickname' => $nickname];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }
}