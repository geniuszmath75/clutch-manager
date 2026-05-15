<?php

namespace Src\Service;

use Core\Auth;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;
use Src\Enum\SystemRole;
use Src\Model\Player;
use Src\Model\Team;
use Src\Repository\TeamRepository;
use Src\Repository\UserRepository;

final class TeamService
{
    public function __construct(
        private readonly TeamRepository $teamRepository,
        private readonly UserRepository $userRepository,
    )
    {
    }

    /**
     * Returns all teams
     *
     * @return Player[]
     */
    public function getAll(): array
    {
        return $this->teamRepository->findAll();
    }

    /**
     * Creates a new team for a COACH who has no team assigned yet.
     * Generates a unique tag from the team name.
     * Assigns the team to the COACH immediately and updates the session.
     *
     * @param array $data
     * @return Team
     */
    public function createTeam(array $data): Team
    {
        Auth::requireRole([SystemRole::Coach->value]);

        $coachId = Auth::userId();

        if (is_null($coachId)) {
            throw new InvalidArgumentException('Authentication failed', 401);
        }

        // COACH may only create a team if they don't have one yet
        if (Auth::teamId() > 0) {
            throw new InvalidArgumentException('User already has a team', 409);
        }

        $name = trim((string) $data['name'] ?? '');

        if (mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Team name must be at least 2 characters', 400);
        }

        if (mb_strlen($name) > 100) {
            throw new InvalidArgumentException('Team name must not exceed 100 characters', 400);
        }

        if ($this->teamRepository->nameExists($name)) {
            throw new InvalidArgumentException('Team name already exists', 409);
        }
        
        $tag = $this->generateUniqueTag($name);
        $team = $this->teamRepository->create($name, $tag);

        // Assign the new team to the COACH and sync the session
        $success = $this->userRepository->assignTeam($coachId, $team->id);
        if (!$success) {
            throw new RuntimeException('Failed to assign a team to user', 500);
        }

        Session::setUserField('team_id', $team->id);
        Session::setUserField('team_name', $team->name);

        return $team;
    }

    /**
     * Generates a unique tag from the team name.
     *
     * Algorithm:
     *   1. Extract uppercase consonants from the name, take the first 4.
     *   2. If fewer than 4 consonants, pad with digits derived from a hash of the name.
     *   3. If the candidate tag already exists in the DB, append an incrementing numeric
     *      suffix (TAG2, TAG3, …) truncated to 10 characters.
     */
    private function generateUniqueTag(string $name): string
    {
        $base = $this->extractBaseTag($name);

        if (!$this->teamRepository->tagExists($base)) {
            return $base;
        }

        // Suffix loop - try TAG2 ... TAG99 before giving up
        for ($i = 2; $i <= 99; $i++) {
            $candidate = mb_substr($base, 0, 10 - mb_strlen((string) $i)) . $i;
            if ($this->teamRepository->tagExists($candidate)) {
                return $candidate;
            }
        }

        // Extremely unlikely fallback — append a short random hex suffix
        return mb_substr($base, 0, 6) . strtoupper(substr(md5(uniqid($name, true)), 0, 4));
    }

    /**
     * Extracts up to 4 uppercase consonants from the name.
     * If fewer than 4 are available, pads with digits from a numeric hash of the name.
     */
    private function extractBaseTag(string $name): string
    {
        $consonants = 'BCDFGHJKLMNPQRSTVWXYZ';
        $upper = strtoupper(preg_replace('/[^a-zA-Z]/', '', $name) ?? '');
        $result = '';

        for ($i = 0; $i < mb_strlen($upper) && mb_strlen($result) < 4; $i++) {
            if (str_contains($consonants, $upper[$i])) {
                $result .= $upper[$i];
            }
        }

        // Pad with digits from crc32 hash if fewer than 4 consonants found
        if (mb_strlen($result) < 4) {
            $hash = (string) abs(crc32($name));
            $padLen = 4 - mb_strlen($result);
            $result .= substr($hash, 0, $padLen);
        }

        return $result;
    }
}