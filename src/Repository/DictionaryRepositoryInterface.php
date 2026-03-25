<?php

declare(strict_types=1);

namespace Src\Repository;

/**
 * Contract for dictionary table repositories.
 *
 * Dictionary tables hold read-only reference data (system_roles, team_roles, etc.).
 * Each row has at minimum: id (int) and ident (string, UPPERCASE).
 */
interface DictionaryRepositoryInterface
{
    /**
     * Return all rows as associative arrays ['id' => int, 'ident' => string].
     *
     * @return array<int, array{id: int, ident: string}>
     */
    public function findAll(): array;

    /**
     * Returns the numeric ID for a given ident value, or null if not found.
     */
    public function findIdByIdent(string $ident): ?int;
}