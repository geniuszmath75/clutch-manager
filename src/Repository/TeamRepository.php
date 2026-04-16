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

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                t.id,
                t.name,
                t.tag
            FROM teams t
            ORDER BY t.name
        ");

        $stmt->execute();

        return array_map(
            fn(array $row) => Team::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}