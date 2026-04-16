<?php

namespace Src\Service;

use InvalidArgumentException;
use RuntimeException;
use Src\Enum\SystemRole;
use Src\Enum\TeamRole;
use Src\Model\Player;
use Src\Repository\PlayerRepository;
use Src\Repository\SystemRoleRepository;
use Src\Repository\TeamRoleRepository;

final class PlayerService
{
    public function __construct(
        private readonly PlayerRepository     $playerRepository,
        private readonly TeamRoleRepository   $teamRoleRepository,
        private readonly SystemRoleRepository $systemRoleRepository
    )
    {
    }

    /**
     * Returns all users with PLAYER system role
     *
     * @return array{players: Player[], total: int, page: int, perPage: int, totalPages: int}
     */
    public function getAll(array $filters = [], int $page = 1, int $pageSize = 5): array
    {
        $page = max(1, $page);
        $pageSize = max(1, min(50, $pageSize));

        if (isset($filters['team_role_ident'])) {
            $filters['team_role_ident'] = $this->validateTeamRoleIdent($filters['team_role_ident']);
        }

        $players = $this->playerRepository->findAll($filters, $page, $pageSize);
        $total = $this->playerRepository->countAll($filters);
        $totalPages = (int)ceil($total / $pageSize);

        return [
            'players' => $players,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Returns user with PLAYER system role and given id
     *
     * @throws RuntimeException if player not exists
     */
    public function getById(int $id): Player
    {
        $player = $this->playerRepository->findById($id);

        if (is_null($player)) {
            throw new RuntimeException('Player not found', 404);
        }

        return $player;
    }

    /**
     * Returns players available to be added to a team
     * (registered, not yet assigned to any team, active).
     *
     * @return Player[]
     */
    public function getAvailable(): array
    {
        return $this->playerRepository->findAvailable();
    }


    /**
     * Updates user with PLAYER system role and given id
     *
     * @param array{nickname?: string, team_role_ident?: string, is_active?: bool} $data
     * @throws InvalidArgumentException with incorrect data
     * @throws RuntimeException if a player not found or in conflict
     */
    public function update(int $id, array $data): Player
    {
        // Check if the player exists
        $this->getById($id);

        $systemRoleId = $this->systemRoleRepository->findIdByIdent(SystemRole::Player->value);

        if (empty($systemRoleId)) {
            throw new InvalidArgumentException('Invalid system role', 400);
        }

        $updateData = [];

        if (isset($data['nickname'])) {
            $nickname = $this->validateNickname($data['nickname']);

            if ($this->playerRepository->nicknameExists($nickname, $id)) {
                throw new RuntimeException('Nickname is already taken.', 409);
            }

            $updateData['nickname'] = $nickname;
        }

        if (array_key_exists('team_role_ident', $data)) {
            if (empty($data['team_role_ident'])) {
                $updateData['team_role_id'] = null;
            } else {
                $this->validateTeamRoleIdent($data['team_role_ident']);
                $teamRoleId = $this->teamRoleRepository->findIdByIdent($data['team_role_ident']);

                if (is_null($teamRoleId)) {
                    throw new InvalidArgumentException('Invalid team role', 400);
                }

                $updateData['team_role_id'] = $teamRoleId;
            }
        }

        if (empty($updateData)) {
            throw new InvalidArgumentException('No data to update', 400);
        }

        $success = $this->playerRepository->update($id, $systemRoleId, $updateData);

        if (!$success) {
            throw new RuntimeException('Failed to update player', 500);
        }

        return $this->getById($id);
    }

    /**
     * Deactivates a player — reversible (is_active = false).
     * ADMIN and COACH.
     *
     * @throws RuntimeException when the player does not exist or is already inactive
     */
    public function deactivate(int $id): void
    {
        $player = $this->getById($id);

        $systemRoleId = $this->systemRoleRepository->findIdByIdent(SystemRole::Player->value);

        if (empty($systemRoleId)) {
            throw new InvalidArgumentException('Invalid system role', 400);
        }

        if (!$player->isActive) {
            throw new RuntimeException('Player is no longer active', 409);
        }

        $success = $this->playerRepository->deactivate($id, $systemRoleId);

        if (!$success) {
            throw new RuntimeException('Failed to deactivate player', 500);
        }
    }

    /**
     * Activates a player — reverse of deactivate().
     * ADMIN and COACH.
     *
     * @throws RuntimeException when the player does not exist or is already active
     */
    public function activate(int $id): void
    {
        $player = $this->getById($id);

        $systemRoleId = $this->systemRoleRepository->findIdByIdent(SystemRole::Player->value);

        if (empty($systemRoleId)) {
            throw new InvalidArgumentException('Invalid system role', 400);
        }

        if ($player->isActive) {
            throw new RuntimeException('Player is already active', 409);
        }

        $success = $this->playerRepository->activate($id, $systemRoleId);

        if (!$success) {
            throw new RuntimeException('Failed to activate player', 500);
        }
    }

    /**
     * Permanently deletes a player — soft delete via deleted_at.
     * ADMIN only.
     *
     * @throws RuntimeException when the player does not exist
     */
    public function delete(int $id): void
    {
        $this->getById($id);

        $systemRoleId = $this->systemRoleRepository->findIdByIdent(SystemRole::Player->value);

        if (empty($systemRoleId)) {
            throw new InvalidArgumentException('Invalid system role', 400);
        }

        $success = $this->playerRepository->delete($id, $systemRoleId);

        if (!$success) {
            throw new RuntimeException('Failed to delete player', 500);
        }
    }

    /**
     * Assigns a player to a team.
     * COACH can only assign to their own team.
     * ADMIN can assign to any team.
     *
     * @throws RuntimeException  when the player does not exist
     * @throws InvalidArgumentException when the player already belongs to a team,
     *                                  or COACH tries to assign to a different team
     */
    public function assignToTeam(int $playerId, int $teamId, string $systemRole, ?int $sessionTeamId): void
    {
        $player = $this->getById($playerId);

        $systemRoleId = $this->systemRoleRepository->findIdByIdent(SystemRole::Player->value);

        if (empty($systemRoleId)) {
            throw new InvalidArgumentException('Invalid system role', 400);
        }

        if (!empty($player->teamId)) {
            throw new RuntimeException('Player already assigned to team', 409);
        }

        if ($systemRole === SystemRole::Coach->value) {
            if (empty($sessionTeamId) || $sessionTeamId !== $teamId) {
                throw new InvalidArgumentException('Coaches can only add players to their own team', 403);
            }
        }

        $success = $this->playerRepository->assignToTeam($playerId, $teamId, $systemRoleId);

        if (!$success) {
            throw new RuntimeException('Failed to assign a player to team', 500);
        }
    }

    /**
     * Removes a player from their team.
     * COACH can only remove players from their own team.
     * ADMIN can remove from any team.
     *
     * @throws RuntimeException  when the player does not exist or does not belong to any team
     * @throws InvalidArgumentException when COACH tries to remove from a different team
     */
    public function removeFromTeam(int $playerId, string $systemRole, ?int $sessionTeamId): void
    {
        $player = $this->getById($playerId);

        $systemRoleId = $this->systemRoleRepository->findIdByIdent(SystemRole::Player->value);

        if (empty($systemRoleId)) {
            throw new InvalidArgumentException('Invalid system role', 400);
        }

        if (empty($player->teamId)) {
            throw new RuntimeException('Player is not assigned to any team', 409);
        }

        if ($systemRole === SystemRole::Coach->value) {
            if (empty($sessionTeamId) || $player->teamId !== $sessionTeamId) {
                throw new InvalidArgumentException('Coaches can only remove players to their own team', 403);
            }
        }

        $success = $this->playerRepository->removeFromTeam($playerId, $systemRoleId);

        if (!$success) {
            throw new RuntimeException('Failed to remove player from team', 500);
        }
    }

    /**
     * Validation
     */

    /**
     * @throws InvalidArgumentException
     */
    private function validateNickname(mixed $nickname): string
    {
        if (!is_string($nickname)) {
            throw new InvalidArgumentException('Nickname must be a string.', 400);
        }

        $nickname = trim($nickname);

        if (strlen($nickname) < 3 || strlen($nickname) > 100) {
            throw new InvalidArgumentException('Nickname must be between 3 and 100 characters.', 400);
        }

        // Allowed: letters, numbers, underscore, dash
        if (!preg_match('/^[\p{L}\p{N}_\-]+$/u', $nickname)) {
            throw new InvalidArgumentException('Nickname contains invalid characters.', 400);
        }

        return $nickname;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateTeamRoleIdent(mixed $ident): string
    {
        if (!is_string($ident)) {
            throw new InvalidArgumentException("Team role identifier must be a string", 400);
        }

        $ident = strtoupper(trim($ident));

        $validIdents = array_map(fn(TeamRole $r) => $r->value, TeamRole::cases());

        if (!in_array($ident, $validIdents, true)) {
            throw new InvalidArgumentException("Invalid team role identifier. Allowed values are: " . implode(', ', $validIdents), 400);
        }

        return $ident;
    }
}