<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\TournamentService;
use Illuminate\Console\Command;
use Exception;
use Illuminate\Support\Collection;

class PlayMatchesCommand extends Command
{
    /** @var string */
    protected $signature = 'cup:play-matches';

    /** @var string */
    protected $description = 'Play all league matches and the final match';

    public function __construct(private readonly TournamentService $tournamentService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Playing league matches...');

        try {
            $this->playAndDisplayMatches();
            $this->playAndDisplayFinalMatch();

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to play matches: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function playAndDisplayMatches(): void
    {
        $this->tournamentService->playLeagueMatches();

        foreach (Team::LEAGUES as $league) {
            $this->displayLeagueInformation($league);
        }
    }

    private function displayLeagueInformation(string $league): void
    {
        $this->info("\nLEAGUE $league");

        $this->info("\nFirst Leg");
        $this->info(str_repeat('-', 65));
        $this->displayMatches($this->tournamentService->getLeagueFirstLegMatches($league));

        $this->info("\nSecond Leg");
        $this->info(str_repeat('-', 65));
        $this->displayMatches($this->tournamentService->getLeagueSecondLegMatches($league));

        $this->displayLeagueStandings($league, $this->tournamentService->getLeagueStandings($league));
    }

    private function displayMatches(Collection $matches): void
    {
        foreach ($matches as $match) {
            $this->info(sprintf(
                " %26s  %d - %d  %-26s",
                $match->homeTeam->name,
                $match->home_score,
                $match->away_score,
                $match->awayTeam->name
            ));
        }
    }

    private function displayLeagueStandings(string $league, Collection $standings): void
    {
        $this->info("\nLeague $league Standings:");
        $this->table(
            ['Team', 'P', 'W', 'D', 'L', 'GF', 'GA', 'GD', 'Pts'],
            $standings->map(function ($standing) {
                return [
                    $standing->team->name,
                    $standing->played,
                    $standing->won,
                    $standing->drawn,
                    $standing->lost,
                    $standing->goals_for,
                    $standing->goals_against,
                    $standing->goal_difference,
                    $standing->points,
                ];
            })
        );
    }

    private function playAndDisplayFinalMatch(): void
    {
        $finalMatch = $this->tournamentService->playFinalMatch();

        $this->info("\nPlaying the Berlinger Club World Cup Final...");

        $this->info(sprintf(
            "\nFinal Match: %s vs %s",
            $finalMatch->homeTeam->name,
            $finalMatch->awayTeam->name
        ));

        $this->info(sprintf(
            "Score: %d - %d",
            $finalMatch->home_score,
            $finalMatch->away_score
        ));

        $winner = $finalMatch->getWinner();
        $this->info(sprintf(
            "\nChampion: %s 🏆",
            $winner->name
        ));
    }
}
