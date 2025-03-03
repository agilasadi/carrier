<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Standing;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class StandingRepository
{
    public function initializeTeamStanding(Team $team): Standing
    {
        return Standing::firstOrCreate(
            ['team_id' => $team->id],
            [
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'points' => 0,
            ]
        );
    }

    public function findByTeam(Team $team): Standing
    {
        return Standing::where('team_id', $team->id)->firstOrFail();
    }

    public function findByLeague(string $league): Collection
    {
        return Standing::query()
            ->with('team')
            ->whereHas('team', function ($query) use ($league) {
                $query->where('league', $league);
            })
            ->get();
    }

    public function sortByRanking(Collection $standings): SupportCollection
    {
        return $standings->sortByDesc(function ($standing) {
            return [
                $standing->points,
                $standing->goal_difference,
                $standing->goals_for
            ];
        });
    }

    public function updateStats(Standing $standing, array $stats): void
    {
        $standing->update([
            'played' => $standing->played + $stats['played'],
            'won' => $standing->won + $stats['won'],
            'drawn' => $standing->drawn + $stats['drawn'],
            'lost' => $standing->lost + $stats['lost'],
            'goals_for' => $standing->goals_for + $stats['goals_for'],
            'goals_against' => $standing->goals_against + $stats['goals_against'],
            'points' => $standing->points + $stats['points'],
        ]);
    }
}
