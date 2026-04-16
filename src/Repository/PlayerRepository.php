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
     * Inactive players (is_active = false) are included - visible to ADMIN and COACH.
     *
     * @return Player[]
     */
    public function findAll(array $filters = [], int $page = 1, int $pageSize = 5): array
    {
        [$conditions, $params] = $this->buildConditions($filters);

        $stmt = $this->pdo->prepare("
            SELECT
                u.id,
                u.nickname,
                u.email,
                sr.ident AS system_role_ident,
                tr.ident AS team_role_ident,
                u.team_id,
                u.is_active
            FROM users u
            JOIN system_roles sr ON sr.id = u.system_role_id
            LEFT JOIN team_roles tr ON tr.id = u.team_role_id
            WHERE sr.ident = :player_role
                AND u.deleted_at IS NULL
                " . $conditions . "
            ORDER BY u.nickname ASC
            LIMIT :page_size OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $offset = ($page - 1) * $pageSize;
        $stmt->bindValue(':page_size', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

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
                u.team_id,
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
     * Returns the total number of players (for pagination).
     * Excludes soft deleted players.
     */
    public function countAll(array $filters = []): int
    {
        [$conditions, $params] = $this->buildConditions($filters);

        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(u.id)
            FROM users u
            JOIN system_roles sr ON sr.id = u.system_role_id
            LEFT JOIN team_roles tr ON tr.id = u.team_role_id
            WHERE sr.ident = :player_role 
                AND u.deleted_at IS NULL
                " . $conditions . "
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Returns players available to be added to a team.
     *
     * For ADMIN: all players without any team (team_id IS NULL).
     * For COACH: all players without any team, regardless of which team the coach manages
     *            (the team itself is enforced at the service layer).
     *
     * Always excludes soft-deleted and inactive players.
     *
     * @return Player[]
     */
    public function findAvailable(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                u.id,
                u.nickname,
                u.email,
                sr.ident AS system_role_ident,
                tr.ident AS team_role_ident,
                u.team_id,
                u.is_active
            FROM users u
            JOIN system_roles sr ON sr.id = u.system_role_id
            LEFT JOIN team_roles tr ON tr.id = u.team_role_id
            WHERE sr.ident = :player_role
              AND u.team_id IS NULL
              AND u.deleted_at IS NULL
              AND u.is_active = true
            ORDER BY u.nickname ASC
        ");
        $stmt->execute(['player_role' => SystemRole::Player->value]);

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
    public function update(int $id, int $systemRoleId, array $data): bool
    {
        $sets = [];
        $params = [':id' => $id, ':system_role_id' => $systemRoleId];

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

        $sql = "UPDATE users
                SET " . implode(', ', $sets) . "
                WHERE id = :id
                AND system_role_id = :system_role_id
                AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() === 1;
    }

    /**
     * Deactivates a player - reversible (is_active = false).
     * The player cannot log in, but appears in the ADMIN/COACH results.
     */
    public function deactivate(int $id, int $systemRoleId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET is_active = false
            WHERE id = :id
                AND system_role_id = :system_role_id
                AND deleted_at IS NULL
        ");

        $params = [':id' => $id, ':system_role_id' => $systemRoleId];

        $stmt->execute($params);

        return $stmt->rowCount() === 1;
    }

    /**
     * Activates the player - reverse deactivate().
     */
    public function activate(int $id, int $systemRoleId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET is_active = true
            WHERE id = :id
                AND system_role_id = :system_role_id
                AND deleted_at IS NULL
        ");

        $params = [':id' => $id, ':system_role_id' => $systemRoleId];

        $stmt->execute($params);

        return $stmt->rowCount() === 1;
    }

    /**
     * Assigns a player to a team by setting users.team_id.
     */
    public function assignToTeam(int $playerId, int $teamId, int $systemRoleId): bool
    {
        $stmt = $this->pdo->prepare("
           UPDATE users
           SET team_id = :team_id
           WHERE id = :id
           AND system_role_id = :system_role_id
           AND deleted_at IS NULL
        ");

        $params = [':id' => $playerId, ':team_id' => $teamId, ':system_role_id' => $systemRoleId];

        $stmt->execute($params);

        return $stmt->rowCount() === 1;
    }

    /**
     * Removes a player from their team by setting users.team_id to NULL.
     */
    public function removeFromTeam(int $id, int $systemRoleId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET team_id = NULL
            WHERE id = :id
            AND system_role_id = :system_role_id
            AND deleted_at IS NULL
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

    /**
     * DELETE
     */

    /**
     * Deletes a player permanently - soft delete via deleted_at.
     * Removes the player from all SELECT results.
     * ADMIN only.
     */
    public function delete(int $id, int $systemRoleId): bool
    {
        $stmt = $this->pdo->prepare("
           UPDATE users
           SET deleted_at = NOW()
           WHERE id = :id
                AND deleted_at IS NULL
                AND system_role_id = :system_role_id
        ");

        $params = [':id' => $id, ':system_role_id' => $systemRoleId];

        $stmt->execute($params);

        return $stmt->rowCount() === 1;
    }


    /**
     * Builds a WHERE fragment and a parameter array based on filters.
     * Filter keys come exclusively from trusted service code—not from user input.
     *
     * @param array{team_role_ident?: string, team_id?: int, is_active?: bool} $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildConditions(array $filters): array
    {
        $conditions = '';
        $params = [];

        $params['player_role'] = SystemRole::Player->value;

        if (isset($filters['team_role_ident'])) {
            $conditions .= ' AND tr.ident = :team_role_ident';
            $params['team_role_ident'] = $filters['team_role_ident'];
        }

        if (isset($filters['team_id'])) {
            $conditions .= ' AND u.team_id = :team_id';
            $params['team_id'] = $filters['team_id'];
        }

        if (isset($filters['is_active'])) {
            $conditions .= ' AND u.is_active = :is_active';
            $params['is_active'] = $filters['is_active'];
        }

        return [$conditions, $params];
    }
}