<?php

namespace Database\Seeders;

use App\Models\GameMatch;
use App\Models\Standing;
use App\Models\Team;
use Illuminate\Database\Seeder;

class MatchSeeder extends Seeder
{
    private array $predefinedMatches = [
        'A' => [
            ['SmartView FC', 'Cornwall United', 1, 1],
            ['Hooligans FC', 'Wild Goats', 1, 0],
            ['Africa United', 'SmartView FC', 2, 1],
            ['Cornwall United', 'Wild Goats', 0, 1],
            ['Africa United', 'Hooligans FC', 5, 0],
            ['Wild Goats', 'SmartView FC', 1, 3],
            ['Cornwall United', 'Africa United', 0, 0],
            ['SmartView FC', 'Hooligans FC', 2, 1],
            ['Wild Goats', 'Africa United', 3, 3],
            ['Hooligans FC', 'Cornwall United', 0, 0],
        ],
        'B' => [
            ['Berlinger Kickers', 'Brave Monkeys', 1, 0],
            ['Green Cats', 'Compassionate Independents', 3, 1],
            ['Curvy Badgers', 'Berlinger Kickers', 1, 1],
            ['Brave Monkeys', 'Compassionate Independents', 0, 2],
            ['Curvy Badgers', 'Green Cats', 3, 1],
            ['Green Cats', 'Brave Monkeys', 0, 0],
            ['Compassionate Independents', 'Berlinger Kickers', 0, 4],
            ['Brave Monkeys', 'Curvy Badgers', 0, 1],
            ['Berlinger Kickers', 'Green Cats', 0, 0],
            ['Compassionate Independents', 'Curvy Badgers', 2, 2],
        ]
    ];

    public function run(): void
    {
        // Clear existing matches only
        GameMatch::truncate();

        foreach (['A', 'B'] as $league) {
            foreach ($this->predefinedMatches[$league] as $match) {
                [$homeTeam, $awayTeam, $homeScore, $awayScore] = $match;
                
                $homeTeamModel = Team::where('name', $homeTeam)->first();
                $awayTeamModel = Team::where('name', $awayTeam)->first();
                
                if (!$homeTeamModel || !$awayTeamModel) {
                    continue;
                }

                // Create first leg match only
                GameMatch::create([
                    'home_team_id' => $homeTeamModel->id,
                    'away_team_id' => $awayTeamModel->id,
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'is_second_leg' => false,
                ]);
            }
        }
    }
} 