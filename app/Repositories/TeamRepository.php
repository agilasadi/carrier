<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class TeamRepository
{
    public function getLeagueWinner(string $league): Team
    {
        return Team::query()
            ->with('standing')
            ->where('league', $league)
            ->join('standings', 'teams.id', '=', 'standings.team_id')
            ->orderByDesc('standings.points')
            ->orderByDesc('standings.goals_for')
            ->orderByRaw('(standings.goals_for - standings.goals_against) DESC')
            ->select('teams.*')
            ->first();
    }

    public function getAllTeams(): Collection
    {
        return Team::all();
    }
}
