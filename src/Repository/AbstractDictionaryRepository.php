<?php

declare(strict_types=1);

namespace Src\Repository;

use PDO;

abstract class AbstractDictionaryRepository implements DictionaryRepositoryInterface
{

    public function __construct(
        protected readonly PDO $pdo
    ) {}

    /**
     * Name of the dictionary table this repository targets.
     */
    abstract protected function tableName(): string;

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        $table = $this->tableName();
        $stmt = $this->pdo->query("SELECT id, ident FROM {$table} ORDER BY id");

        return array_map(
            static fn(array $row) => ['id' => (int) $row['id'], 'ident' => $row['ident']],
            $stmt->fetchAll()
        );
    }

    /**
     * @inheritDoc
     */
    public function findIdByIdent(string $ident): ?int
    {
        $table = $this->tableName();
        $stmt = $this->pdo->prepare("SELECT id FROM {$table} WHERE ident = :ident LIMIT 1");
        $stmt->execute(['ident' => $ident]);

        $result = $stmt->fetchColumn();
        return $result !== false ? (int) $result : null;
    }
}