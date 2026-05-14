<?php

namespace Src\Model;

final class Team
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly string $tag,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    )
    {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            name: $row['name'],
            tag: $row['tag'],
            createdAt: (string)$row['created_at'],
            updatedAt: (string)$row['updated_at'],
        );
    }
}