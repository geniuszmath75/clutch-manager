<?php

declare(strict_types=1);

namespace Src\Repository;

use Core\Database;
use PDO;
use Src\Model\Strategy;

final class StrategyRepository
{
    private PDO $pdo;

    public function __construct(
        private PlayerRepository $playerRepository
    )
    {
        $this->pdo = Database::getInstance()->getPDO();
    }

    /**
     * READ
     */
    public function findAll(array $filters = [], int $page = 1, int $pageSize = 5): array
    {
        [$conditions, $params] = $this->buildConditions($filters);

        $stmt = $this->pdo->prepare("
            SELECT
                ts.id,
                ts.name,
                ts.description,
                ts.steps_to_do,
                gm.ident          AS map_ident,
                st.ident          AS strategy_type_ident,
                ts.team_id,
                ts.map_id,
                ts.strategy_type_id,
                ts.created_at,
                ts.updated_at
            FROM team_strategies ts
            JOIN game_maps       gm ON gm.id = ts.map_id
            JOIN strategy_types  st ON st.id = ts.strategy_type_id
            WHERE ts.deleted_at IS NULL
              $conditions
            ORDER BY ts.created_at DESC
            LIMIT :pageSize OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $offset = ($page - 1) * $pageSize;
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        $strategies = array_map(fn(array $row) => Strategy::fromRow($row), $rows);
        $strategyIds = array_map(fn(Strategy $s) => $s->id, $strategies);
        $playersMap = $this->playerRepository->findByStrategyIds($strategyIds);

        return array_map(
            fn(Strategy $strategy) => new Strategy(
                id: $strategy->id,
                name: $strategy->name,
                description: $strategy->description,
                stepsToDo: $strategy->stepsToDo,
                mapIdent: $strategy->mapIdent,
                strategyTypeIdent: $strategy->strategyTypeIdent,
                teamId: $strategy->teamId,
                mapId: $strategy->mapId,
                strategyTypeId: $strategy->strategyTypeId,
                createdAt: $strategy->createdAt,
                updatedAt: $strategy->updatedAt,
                players: $playersMap[$strategy->id] ?? []
            ),
            $strategies,
        );
    }

    public function countAll(array $filters = []): int
    {
        [$conditions, $params] = $this->buildConditions($filters);

        $sql = "
            SELECT COUNT(ts.id)
            FROM team_strategies ts
            WHERE ts.deleted_at IS NULL
              $conditions
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Fetch a single strategy with its assigned players.
     */
    public function findById(int $id): ?Strategy
    {
        $stmt = $this->pdo->prepare("
            SELECT
                ts.id,
                ts.name,
                ts.description,
                ts.steps_to_do,
                gm.ident          AS map_ident,
                st.ident          AS strategy_type_ident,
                ts.team_id,
                ts.map_id,
                ts.strategy_type_id,
                ts.created_at,
                ts.updated_at
            FROM team_strategies ts
            JOIN game_maps       gm ON gm.id = ts.map_id
            JOIN strategy_types  st ON st.id = ts.strategy_type_id
            WHERE ts.id = :id
              AND ts.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $strategy = Strategy::fromRow($row);
        $players = $this->playerRepository->findByStrategyIds([$id])[$id] ?? [];

        // Attach players via constructor promotion — rebuild with players
        return new Strategy(
            id: $strategy->id,
            name: $strategy->name,
            description: $strategy->description,
            stepsToDo: $strategy->stepsToDo,
            mapIdent: $strategy->mapIdent,
            strategyTypeIdent: $strategy->strategyTypeIdent,
            teamId: $strategy->teamId,
            mapId: $strategy->mapId,
            strategyTypeId: $strategy->strategyTypeId,
            createdAt: $strategy->createdAt,
            updatedAt: $strategy->updatedAt,
            players: $players
        );
    }

    /**
     * Check if a strategy with the same name already exists in the team (excluding deleted).
     */
    public function strategyExistsInTeam(int $teamId, string $strategyName): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM team_strategies
            WHERE team_id = :team_id
                AND name = :strategy_name
                AND deleted_at IS NULL
        ");

        $params = ['team_id' => $teamId, 'strategy_name' => $strategyName];
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * WRITE
     */
    public function create(array $data): Strategy
    {
        return Database::getInstance()->transaction(function () use ($data): Strategy {
            $sql = "
            INSERT INTO team_strategies
                (name, description, steps_to_do, team_id, map_id, strategy_type_id, updated_by_user_id)
            VALUES
                (:name, :description, :steps_to_do, :team_id, :map_id, :strategy_type_id, :updated_by_user_id)
            RETURNING id
        ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':steps_to_do' => json_encode($data['steps_to_do'] ?? [], JSON_THROW_ON_ERROR),
                ':team_id' => $data['team_id'],
                ':map_id' => $data['map_id'],
                ':strategy_type_id' => $data['strategy_type_id'],
                ':updated_by_user_id' => $data['updated_by_user_id'],
            ]);

            $strategyId = (int)$stmt->fetchColumn();

            if (!empty($data['player_ids'])) {
                $this->syncPlayers($strategyId, $data['player_ids']);
            }

            return $this->findById($strategyId);
        });
    }

    public function update(int $id, array $data): Strategy
    {
        return Database::getInstance()->transaction(function () use ($id, $data): Strategy {
            $sets = [];
            $params = [':id' => $id];

            $allowed = ['map_id', 'strategy_type_id', 'name', 'description', 'steps_to_do', 'updated_by_user_id'];

            foreach ($allowed as $field) {
                if (!array_key_exists($field, $data)) {
                    continue;
                }

                $sets[] = "$field = :$field";

                if ($field === 'steps_to_do') {
                    $params[":$field"] = json_encode($data[$field], JSON_THROW_ON_ERROR);
                } else {
                    $params[":$field"] = $data[$field];
                }
            }

            if (!empty($sets)) {
                $sets[] = 'updated_at = NOW()';
                $stmt = $this->pdo->prepare("
                    UPDATE team_strategies 
                    SET " . implode(', ', $sets) . " 
                    WHERE id = :id 
                        AND deleted_at IS NULL"
                );
                $stmt->execute($params);
            }

            // Sync players only when key is explicitly provided AND the set has actually changed
            if (array_key_exists('player_ids', $data)) {
                $incomingIds = array_map('intval', $data['player_ids']);
                $currentIds = array_map(
                    fn(array $players) => (int)$players['id'],
                    $this->playerRepository->findByStrategyIds([$id])[$id] ?? []
                );

                sort($incomingIds);
                sort($currentIds);

                if ($incomingIds !== $currentIds) {
                    $this->syncPlayers($id, $data['player_ids']);
                }
            }

            return $this->findById($id);
        });
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE team_strategies 
            SET deleted_at = NOW(), 
                updated_at = NOW(),
                updated_by_user_id = :updated_by_user_id
            WHERE id = :id 
              AND deleted_at IS NULL"
        );
        $stmt->execute(['id' => $id, 'updated_by_user_id' => $userId]);

        return $stmt->rowCount() === 1;
    }

    /**
     * HELPERS
     */

    /**
     * Replace all player assignments for a strategy.
     * @param int[] $playerIds
     */
    private function syncPlayers(int $strategyId, array $playerIds): void
    {
        // Remove all existing assignments
        $del = $this->pdo->prepare('DELETE FROM team_strategy_player WHERE team_strategy_id = :sid');
        $del->bindValue(':sid', $strategyId, PDO::PARAM_INT);
        $del->execute();

        if (empty($playerIds)) {
            return;
        }

        $ins = $this->pdo->prepare(
            'INSERT INTO team_strategy_player (team_strategy_id, player_id) VALUES (:sid, :pid) ON CONFLICT DO NOTHING'
        );

        foreach ($playerIds as $pid) {
            $ins->bindValue(':sid', $strategyId, PDO::PARAM_INT);
            $ins->bindValue(':pid', $pid, PDO::PARAM_INT);
            $ins->execute();
        }
    }

    /** @return array{string, array<string, mixed>} */
    private function buildConditions(array $filters): array
    {
        $conditions = '';
        $params = [];

        if (!empty($filters['team_id'])) {
            $conditions .= ' AND ts.team_id = :team_id';
            $params[':team_id'] = (int)$filters['team_id'];
        }

        if (!empty($filters['map_id'])) {
            $conditions .= ' AND ts.map_id = :map_id';
            $params[':map_id'] = (int)$filters['map_id'];
        }

        if (!empty($filters['strategy_type_id'])) {
            $conditions .= ' AND ts.strategy_type_id = :strategy_type_id';
            $params[':strategy_type_id'] = (int)$filters['strategy_type_id'];
        }

        if (!empty($filters['name'])) {
            $conditions .= ' AND ts.name ILIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }

        return [$conditions, $params];
    }
}