<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class GameMatchRepository
{
    public function getLeagueMatches(string $league): Collection
    {
        return GameMatch::query()
            ->with(['homeTeam', 'awayTeam'])
            ->where('is_final', false)
            ->where(function ($query) use ($league) {
                $query->whereHas('homeTeam', function ($q) use ($league) {
                    $q->where('league', $league);
                })->whereHas('awayTeam', function ($q) use ($league) {
                    $q->where('league', $league);
                });
            })
            ->orderBy('is_second_leg')
            ->orderBy('id')
            ->get();
    }

    public function generateSecondLegMatches(string $league): void
    {
        $firstLegMatches = GameMatch::query()
            ->where('is_final', false)
            ->where('is_second_leg', false)
            ->where(function ($query) use ($league) {
                $query->whereHas('homeTeam', function ($q) use ($league) {
                    $q->where('league', $league);
                })->whereHas('awayTeam', function ($q) use ($league) {
                    $q->where('league', $league);
                });
            })
            ->get();

        foreach ($firstLegMatches as $firstLegMatch) {
            GameMatch::firstOrCreate(
                [
                    'home_team_id' => $firstLegMatch->away_team_id,
                    'away_team_id' => $firstLegMatch->home_team_id,
                    'is_final' => false,
                    'is_second_leg' => true,
                ],
                [
                    'home_score' => null,
                    'away_score' => null,
                ]
            );
        }
    }

    public function simulateMatchScore(GameMatch $match): void
    {
        $match->update([
            'home_score' => rand(0, 7),
            'away_score' => rand(0, 7),
        ]);
    }

    private function generateUniqueScores(): array
    {
        $homeScore = rand(0, 5);
        $awayScore = rand(0, 5);

        while ($homeScore === $awayScore) {
            $homeScore = rand(0, 5);
            $awayScore = rand(0, 5);
        }

        return [$homeScore, $awayScore];
    }

    public function createFinalMatch(Team $homeTeam, Team $awayTeam): GameMatch
    {
        $match = GameMatch::create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'is_final' => true,
        ]);

        [$homeScore, $awayScore] = $this->generateUniqueScores();

        $match->update([
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ]);

        return $match;
    }

    public function getLeagueFirstLegMatches(string $league): Collection
    {
        return GameMatch::query()
            ->with(['homeTeam', 'awayTeam'])
            ->where('is_final', false)
            ->where('is_second_leg', false)
            ->where(function ($query) use ($league) {
                $query->whereHas('homeTeam', function ($q) use ($league) {
                    $q->where('league', $league);
                })->whereHas('awayTeam', function ($q) use ($league) {
                    $q->where('league', $league);
                });
            })
            ->orderBy('id')
            ->get();
    }

    public function getLeagueSecondLegMatches(string $league): Collection
    {
        return GameMatch::query()
            ->with(['homeTeam', 'awayTeam'])
            ->where('is_final', false)
            ->where('is_second_leg', true)
            ->where(function ($query) use ($league) {
                $query->whereHas('homeTeam', function ($q) use ($league) {
                    $q->where('league', $league);
                })->whereHas('awayTeam', function ($q) use ($league) {
                    $q->where('league', $league);
                });
            })
            ->orderBy('id')
            ->get();
    }
} 