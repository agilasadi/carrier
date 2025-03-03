<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // League A teams
        $leagueATeams = [
            'SmartView FC',
            'Hooligans FC',
            'Africa United',
            'Cornwall United',
            'Wild Goats',
        ];

        foreach ($leagueATeams as $teamName) {
            Team::create([
                'name' => $teamName,
                'league' => 'A',
            ]);
        }

        // League B teams
        $leagueBTeams = [
            'Berlinger Kickers',
            'Green Cats',
            'Curvy Badgers',
            'Brave Monkeys',
            'Compassionate Independents',
        ];

        foreach ($leagueBTeams as $teamName) {
            Team::create([
                'name' => $teamName,
                'league' => 'B',
            ]);
        }
    }
}
