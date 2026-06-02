<?php

declare(strict_types=1);

namespace Src\Service;

use Core\Auth;
use InvalidArgumentException;
use RuntimeException;
use Src\Enum\SystemRole;
use Src\Model\Strategy;
use Src\Repository\StrategyRepository;

final class StrategyService
{
    public function __construct(
        private readonly StrategyRepository $strategyRepository,
    )
    {
    }

    /**
     * Returns list of team strategies
     */

    public function getAll(array $rawFilters = [], int $page = 1, int $pageSize = 5): array
    {
        $filters = $this->buildFilters($rawFilters);
        $page = max(1, $page);
        $pageSize = max(1, min(50, $pageSize));

        $strategies = $this->strategyRepository->findAll($filters, $page, $pageSize);
        $total = $this->strategyRepository->countAll($filters);
        $totalPages = (int)ceil($total / $pageSize);

        return [
            'strategies' => $strategies,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
        ];
    }

    public function getById(int $id): Strategy
    {
        $strategy = $this->strategyRepository->findById($id);

        if (is_null($strategy)) {
            throw new InvalidArgumentException('Strategy not found.', 404);
        }

        $this->assertReadAccess($strategy->teamId);

        return $strategy;
    }

    /**
     * Creates a strategy
     */
    public function create(array $data): Strategy
    {
        Auth::requireRole([SystemRole::Admin->value, SystemRole::Coach->value]);

        $validated = $this->validateData($data);

        // COACH: team_id forced from session, cannot create for another team
        if (Auth::systemRole() === SystemRole::Coach->value) {
            $validated['team_id'] = Auth::teamId()
                ?? throw new InvalidArgumentException('Coach has no team assigned.', 403);
        }

        $isStrategyExists = $this->strategyRepository->strategyExistsInTeam($validated['team_id'], $validated['name']);
        if ($isStrategyExists) {
            throw new InvalidArgumentException('Strategy with the same name already exists in the team.', 409);
        }

        return $this->strategyRepository->create($validated);
    }

    /**
     * Updates a strategy
     *
     * PLAYER can edit (but not create/delete);
     * COACH & ADMIN can also edit
     */
    public function update(int $id, array $data): Strategy
    {
        Auth::requireRole([SystemRole::Admin->value, SystemRole::Coach->value, SystemRole::Player->value]);

        $strategy = $this->strategyRepository->findById($id);

        if (is_null($strategy)) {
            throw new InvalidArgumentException('Strategy not found.', 404);
        }

        $this->assertWriteAccess($strategy->teamId);

        // Prevent changing team ownership via payload
        unset($data['team_id']);

        $strategyData = $this->validatePartialData($data);

        $teamId = Auth::teamId();
        $isStrategyExists = $this->strategyRepository->strategyExistsInTeam($teamId, $strategyData['name']);
        if ($isStrategyExists) {
            throw new InvalidArgumentException('Strategy with the same name already exists in the team.', 409);
        }

        return $this->strategyRepository->update($id, $strategyData);
    }

    /**
     * Permanently deletes a strategy - soft delete via deleted_at
     */
    public function delete(int $id): void
    {
        Auth::requireRole([SystemRole::Admin->value, SystemRole::Coach->value]);

        $strategy = $this->strategyRepository->findById($id);

        if (is_null($strategy)) {
            throw new InvalidArgumentException('Strategy not found.', 404);
        }

        $this->assertWriteAccess($strategy->teamId);
        $userId = Auth::userId();

        $success = $this->strategyRepository->delete($id, $userId);

        if (!$success) {
            throw new RuntimeException('Failed to delete strategy.', 500);
        }
    }

    /**
     * HELPERS
     */

    /**
     * Force team_id filter from session for PLAYER and COACH.
     * ADMIN may optionally filter by any team_id.
     */
    private function buildFilters(array $raw): array
    {
        $systemRole = Auth::systemRole();

        if ($systemRole === SystemRole::Admin->value) {
            $filters = [];

            if (!empty($raw['team_id'])) {
                $filters['team_id'] = (int)$raw['team_id'];
            }
        } else {
            // COACH and PLAYER always scoped to their own team
            $teamId = Auth::teamId();

            if (is_null($teamId)) {
                throw new InvalidArgumentException('User has no team assigned.', 403);
            }

            $filters = ['team_id' => $teamId];
        }

        if (!empty($raw['map_id'])) {
            $filters['map_id'] = (int)$raw['map_id'];
        }

        if (!empty($raw['strategy_type_id'])) {
            $filters['strategy_type_id'] = (int)$raw['strategy_type_id'];
        }

        if (!empty($raw['name'])) {
            $filters['name'] = trim((string)$raw['name']);
        }

        return $filters;
    }

    /**
     * Full validation for create.
     */
    private function validateData(array $data): array
    {
        $errors = [];

        if (Auth::systemRole() === SystemRole::Admin->value) {
            if (empty($data['team_id']) || !is_numeric($data['team_id'])) {
                $errors[] = 'team_id is required for ADMIN.';
            }
        }

        if (empty($data['map_id']) || !is_numeric($data['map_id'])) {
            $errors[] = 'map_id is required.';
        }

        if (empty($data['strategy_type_id']) || !is_numeric($data['strategy_type_id'])) {
            $errors[] = 'strategy_type_id is required.';
        }

        $name = trim((string)($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 255) {
            $errors[] = 'name is required and must be at most 255 characters.';
        }

        $description = trim((string)($data['description'] ?? ''));
        if ($description === '') {
            $errors[] = 'description is required.';
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(' ', $errors), 400);
        }

        $stepsToDo = $data['steps_to_do'] ?? [];
        if (!is_array($stepsToDo)) {
            throw new InvalidArgumentException('steps_to_do must be an array.', 400);
        }

        $playerIds = $data['player_ids'] ?? [];
        if (!is_array($playerIds)) {
            throw new InvalidArgumentException('player_ids must be an array.', 400);
        }

        if (sizeof($playerIds) > 5) {
            throw new InvalidArgumentException('Number of assigned players exceeds the maximum number of team members.', 400);
        }

        return [
            'team_id' => isset($data['team_id']) ? (int)$data['team_id'] : null,
            'map_id' => (int)$data['map_id'],
            'strategy_type_id' => (int)$data['strategy_type_id'],
            'name' => $name,
            'description' => isset($data['description']) ? trim((string)$data['description']) : null,
            'steps_to_do' => $stepsToDo,
            'player_ids' => array_map('intval', $playerIds),
            'updated_by_user_id' => Auth::userId(),
        ];
    }

    /**
     * Partial validation for update (all fields optional).
     */
    private function validatePartialData(array $data): array
    {
        $validated = [];

        if (array_key_exists('map_id', $data)) {
            if (!is_numeric($data['map_id'])) {
                throw new InvalidArgumentException('map_id must be a number.', 422);
            }
            $validated['map_id'] = (int)$data['map_id'];
        }

        if (array_key_exists('strategy_type_id', $data)) {
            if (!is_numeric($data['strategy_type_id'])) {
                throw new InvalidArgumentException('strategy_type_id must be a number.', 422);
            }
            $validated['strategy_type_id'] = (int)$data['strategy_type_id'];
        }

        if (array_key_exists('name', $data)) {
            $name = trim((string)$data['name']);
            if ($name === '' || mb_strlen($name) > 255) {
                throw new InvalidArgumentException('name must be between 1 and 255 characters.', 422);
            }
            $validated['name'] = $name;
        }

        if (array_key_exists('description', $data)) {
            $validated['description'] = isset($data['description']) ? trim((string)$data['description']) : null;
        }

        if (array_key_exists('steps_to_do', $data)) {
            if (!is_array($data['steps_to_do'])) {
                throw new InvalidArgumentException('steps_to_do must be an array.', 422);
            }
            $validated['steps_to_do'] = $data['steps_to_do'];
        }

        if (array_key_exists('player_ids', $data)) {
            if (!is_array($data['player_ids'])) {
                throw new InvalidArgumentException('player_ids must be an array.', 422);
            }
            $validated['player_ids'] = array_map('intval', $data['player_ids']);
        }

        $validated['updated_by_user_id'] = Auth::userId();

        return $validated;
    }

    /**
     * Check that the current user can READ resources belonging to $teamId.
     * ADMIN: always; COACH/PLAYER: only own team.
     */
    private function assertReadAccess(int $teamId): void
    {
        if (Auth::systemRole() === SystemRole::Admin->value) {
            return;
        }

        if (Auth::teamId() !== $teamId) {
            throw new InvalidArgumentException('Access denied.', 403);
        }
    }

    /**
     * Check that the current user can WRITE resources belonging to $teamId.
     * Same logic as read access — team ownership enforced here.
     */
    private function assertWriteAccess(int $teamId): void
    {
        if (Auth::systemRole() === SystemRole::Admin->value) {
            return;
        }

        if (Auth::teamId() !== $teamId) {
            throw new InvalidArgumentException('Access denied.', 403);
        }
    }
}