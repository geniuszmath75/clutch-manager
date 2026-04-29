<?php

namespace Src\Model;

final class PlayerMatchStats
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $killsNumber,
        public readonly int    $deathsNumber,
        public readonly int    $assistsNumber,
        public readonly int    $flashAssistsNumber,
        public readonly int    $totalDamage,
        public readonly float  $hsPercent,
        public readonly int    $rkastNumber, // RoundsWithKillAssistSurvivedTraded <= team+opponent score
        public readonly string $playerNickname,
        public readonly int    $matchId,
        public readonly int    $playerId
    )
    {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            killsNumber: (int)$row['kills_number'],
            deathsNumber: (int)$row['deaths_number'],
            assistsNumber: (int)$row['assists_number'],
            flashAssistsNumber: (int)$row['flash_assists_number'],
            totalDamage: (int)$row['total_damage'],
            hsPercent: (float)$row['hs_percent'],
            rkastNumber: (int)$row['rkast_number'],
            playerNickname: $row['player_nickname'],
            matchId: (int)$row['match_id'],
            playerId: (int)$row['player_id']
        );
    }

    /** K/D ratio — avoid division by zero */
    public function kd(): float
    {
        return $this->deathsNumber === 0 ? (float)$this->killsNumber : round(($this->killsNumber / $this->deathsNumber), 2);
    }

    /** Plus/minus = kills - deaths */
    public function plusMinus(): float
    {
        return $this->killsNumber - $this->deathsNumber;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'killsNumber' => $this->killsNumber,
            'deathsNumber' => $this->deathsNumber,
            'assistsNumber' => $this->assistsNumber,
            'flashAssistsNumber' => $this->flashAssistsNumber,
            'totalDamage' => $this->totalDamage,
            'hsPercent' => $this->hsPercent,
            'rkastNumber' => $this->rkastNumber,
            'playerNickname' => $this->playerNickname,
            'matchId' => $this->matchId,
            'playerId' => $this->playerId,
            'kd' => $this->kd(),
            'plusMinus' => $this->plusMinus()
        ];
    }
}