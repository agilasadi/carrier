<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MatchPoints;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property BelongsTo $home_score
 * @property mixed $away_score
 */
class GameMatch extends Model
{
    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'is_final',
        'is_second_leg',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'is_second_leg' => 'boolean',
    ];

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function isPlayed(): bool
    {
        return !is_null($this->home_score) && !is_null($this->away_score);
    }

    public function getWinner(): ?Team
    {
        if (!$this->isPlayed()) {
            return null;
        }

        if ($this->home_score > $this->away_score) {
            return $this->homeTeam;
        }

        if ($this->away_score > $this->home_score) {
            return $this->awayTeam;
        }

        return null; // Draw
    }

    /**
     * Get match statistics for the away team.
     *
     * @return array{
     *     played: int,
     *     won?: int,
     *     drawn?: int,
     *     lost?: int,
     *     goals_for: int,
     *     goals_against: int,
     *     points: int
     * }
     */
    public function getStatsForAwayTeam(): array
    {
        if (!$this->isPlayed()) {
            return [
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'points' => 0,
            ];
        }

        if ($this->isHomeTeamWinner()) {
            return [
                'played' => 1,
                'won' => 0,
                'drawn' => 0,
                'lost' => 1,
                'goals_for' => $this->away_score,
                'goals_against' => $this->home_score,
                'points' => MatchPoints::LOSE->value,
            ];
        }

        if ($this->isAwayTeamWinner()) {
            return [
                'played' => 1,
                'won' => 1,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => $this->away_score,
                'goals_against' => $this->home_score,
                'points' => MatchPoints::WIN->value,
            ];
        }

        return [
            'played' => 1,
            'won' => 0,
            'drawn' => 1,
            'lost' => 0,
            'goals_for' => $this->away_score,
            'goals_against' => $this->home_score,
            'points' => MatchPoints::DRAW->value,
        ];
    }

    /**
     * Get match statistics for the home team.
     *
     * @return array{
     *     played: int,
     *     won?: int,
     *     drawn?: int,
     *     lost?: int,
     *     goals_for: int,
     *     goals_against: int,
     *     points: int
     * }
     */
    public function getStatsForHomeTeam(): array
    {
        if (!$this->isPlayed()) {
            return [
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'points' => 0,
            ];
        }

        if ($this->isHomeTeamWinner()) {
            return [
                'played' => 1,
                'won' => 1,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => $this->home_score,
                'goals_against' => $this->away_score,
                'points' => MatchPoints::WIN->value,
            ];
        }

        if ($this->isAwayTeamWinner()) {
            return [
                'played' => 1,
                'won' => 0,
                'drawn' => 0,
                'lost' => 1,
                'goals_for' => $this->home_score,
                'goals_against' => $this->away_score,
                'points' => MatchPoints::LOSE->value,
            ];
        }

        return [
            'played' => 1,
            'won' => 0,
            'drawn' => 1,
            'lost' => 0,
            'goals_for' => $this->home_score,
            'goals_against' => $this->away_score,
            'points' => MatchPoints::DRAW->value,
        ];
    }

    private function isHomeTeamWinner(): bool
    {
        return $this->home_score > $this->away_score;
    }

    private function isAwayTeamWinner(): bool
    {
        return $this->away_score > $this->home_score;
    }
}
