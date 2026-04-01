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
     * @return Player[]
     */
    public function getAll(): array
    {
        return $this->playerRepository->findAll();
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
     * Returns users with PLAYER system role and given team role
     *
     * @return Player[]
     * @throws InvalidArgumentException gdy rola jest nieprawidłowa
     */
    public function getByTeamRole(string $teamRoleIdent): array
    {
        $teamRoleIdent = $this->validateTeamRoleIdent($teamRoleIdent);
        return $this->playerRepository->findByTeamRole($teamRoleIdent);
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

        $success = $this->playerRepository->update($id, $updateData);

        if (!$success) {
            throw new RuntimeException('Failed to update player', 500);
        }

        return $this->getById($id);
    }

    public function deactivate(int $id): void
    {
        $this->getById($id);

        $systemRoleId = $this->systemRoleRepository->findIdByIdent(SystemRole::Player->value);

        if (empty($systemRoleId)) {
            throw new InvalidArgumentException('Invalid system role', 400);
        }

        $success = $this->playerRepository->deactivate($id, $systemRoleId);

        if (!$success) {
            throw new RuntimeException('Failed to deactivate player', 500);
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
            throw new InvalidArgumentException('Nickname must be a string.');
        }

        $nickname = trim($nickname);

        if (strlen($nickname) < 3 || strlen($nickname) > 100) {
            throw new InvalidArgumentException('Nickname must be between 3 and 100 characters.');
        }

        // Allowed: letters, numbers, underscore, dash
        if (!preg_match('/^[\p{L}\p{N}_\-]+$/u', $nickname)) {
            throw new InvalidArgumentException('Nickname contains invalid characters.');
        }

        return $nickname;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateTeamRoleIdent(mixed $ident): string
    {
        if (!is_string($ident)) {
            throw new InvalidArgumentException("Team role identifier must be a string");
        }

        $ident = strtoupper(trim($ident));

        $validIdents = array_map(fn(TeamRole $r) => $r->value, TeamRole::cases());

        if (!in_array($ident, $validIdents, true)) {
            throw new InvalidArgumentException("Invalid team role identifier. Allowed values are: " . implode(', ', $validIdents));
        }

        return $ident;
    }
}