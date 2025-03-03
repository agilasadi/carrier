<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Team;
use App\Repositories\GameMatchRepository;
use App\Repositories\StandingRepository;
use App\Repositories\TeamRepository;
use Illuminate\Support\Collection;

readonly class TournamentService
{
    public function __construct(
        private TeamRepository      $teamRepository,
        private GameMatchRepository $matchRepository,
        private StandingRepository  $standingRepository,
    ) {
    }

    public function playLeagueMatches(): void
    {
        $teams = $this->teamRepository->getAllTeams();
        foreach ($teams as $team) {
            $this->standingRepository->initializeTeamStanding($team);
        }

        $this->generateSecondLegMatches();

        foreach (Team::LEAGUES as $league) {
            $this->playLeague($league);
        }
    }

    private function playLeague(string $league): void
    {
        $matches = $this->matchRepository->getLeagueMatches($league);

        /** @var GameMatch $match */
        foreach ($matches as $match) {
            if (!$match->isPlayed()) {
                $this->matchRepository->simulateMatchScore($match);
            }

            $this->updateStandings($match);
        }
    }

    public function generateSecondLegMatches(): void
    {
        foreach (Team::LEAGUES as $league) {
            $this->matchRepository->generateSecondLegMatches($league);
        }
    }

    private function updateStandings(GameMatch $match): void
    {
        $homeTeamStanding = $this->standingRepository->findByTeam($match->homeTeam);
        $awayTeamStanding = $this->standingRepository->findByTeam($match->awayTeam);

        $this->standingRepository->updateStats($homeTeamStanding, $match->getStatsForHomeTeam());
        $this->standingRepository->updateStats($awayTeamStanding, $match->getStatsForAwayTeam());
    }

    public function getLeagueStandings(string $league): Collection
    {
        $standings = $this->standingRepository->findByLeague($league);

        return $this->standingRepository->sortByRanking($standings);
    }

    public function playFinalMatch(): GameMatch
    {
        $leagueAWinner = $this->teamRepository->getLeagueWinner(Team::LEAGUE_A);
        $leagueBWinner = $this->teamRepository->getLeagueWinner(Team::LEAGUE_B);

        return $this->matchRepository->createFinalMatch(
            $leagueAWinner,
            $leagueBWinner
        );
    }

    public function getLeagueFirstLegMatches(string $league): Collection
    {
        return $this->matchRepository->getLeagueFirstLegMatches($league);
    }

    public function getLeagueSecondLegMatches(string $league): Collection
    {
        return $this->matchRepository->getLeagueSecondLegMatches($league);
    }
}
