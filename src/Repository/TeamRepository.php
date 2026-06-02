<?php

namespace Src\Repository;

use Core\Database;
use PDO;
use Src\Model\Team;

final class TeamRepository
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
     * Returns all teams
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                t.id,
                t.name,
                t.tag,
                t.created_at,
                t.updated_at
            FROM teams t
            ORDER BY t.name
        ");

        $stmt->execute();

        return array_map(
            fn(array $row) => Team::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Returns a team by id
     */
    public function findById(int $id): ?Team
    {
        $stmt = $this->pdo->prepare("SELECT * FROM teams WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Team::fromRow($row) : null;
    }

    /**
     * Checks whether a team name is already taken (case-insensitive).
     */
    public function nameExists(string $name): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM teams
            WHERE LOWER(name) = LOWER(:name)
        ");
        $params = [':name' => $name];
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Checks whether a tag is already taken.
     */
    public function tagExists(string $tag): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM teams WHERE tag = :tag
        ");
        $params = [':tag' => $tag];
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * CREATE
     */

    /**
     * Inserts a new team row and returns the created Team model.
     */
    public function create(string $name, string $tag): Team
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO teams (name, tag, created_at, updated_at)
            VALUES (:name, :tag, NOW(), NOW())
            RETURNING id
        ");
        $params = [':name' => $name, ':tag' => $tag];
        $stmt->execute($params);

        $teamId = (int)$stmt->fetchColumn();

        return $this->findById($teamId);
    }
}