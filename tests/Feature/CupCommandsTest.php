<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Standing;
use App\Models\Team;
use Database\Seeders\MatchSeeder;
use Database\Seeders\TeamSeeder;
use Tests\TestCase;
use Mockery;
use Illuminate\Database\Eloquent\Collection;
use App\Services\TournamentService;
use App\Repositories\TeamRepository;
use App\Repositories\GameMatchRepository;
use App\Repositories\StandingRepository;

/**
 * @coversDefaultClass \App\Console\Commands\PlayMatchesCommand
 */
class CupCommandsTest extends TestCase
{
    private TournamentService $service;
    private TeamRepository $teamRepository;
    private GameMatchRepository $matchRepository;
    private StandingRepository $standingRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Only seed what we need
        $this->seed(TeamSeeder::class);
        $this->seed(MatchSeeder::class);

        $this->teamRepository = Mockery::mock(TeamRepository::class);
        $this->matchRepository = Mockery::mock(GameMatchRepository::class);
        $this->standingRepository = Mockery::mock(StandingRepository::class);

        $this->service = new TournamentService(
            $this->teamRepository,
            $this->matchRepository,
            $this->standingRepository
        );

        $this->app->instance(TournamentService::class, $this->service);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_play_matches_command(): void
    {
        $this->teamRepository->shouldReceive('getAllTeams')
            ->once()
            ->andReturn(Collection::make([
                new Team(['name' => 'SmartView FC', 'league' => 'A']),
                new Team(['name' => 'Cornwall United', 'league' => 'A']),
                new Team(['name' => 'Hooligans FC', 'league' => 'B']),
                new Team(['name' => 'Wild Goats', 'league' => 'B']),
            ]));

        $this->standingRepository->shouldReceive('initializeTeamStanding')
            ->times(4)
            ->andReturn(new Standing());

        $this->matchRepository->shouldReceive('getLeagueMatches')
            ->with('A')
            ->andReturn(Collection::make([
                $this->createMatch('SmartView FC', 'Cornwall United', 2, 0),
                $this->createMatch('Cornwall United', 'SmartView FC', 1, 3),
            ]));

        $this->matchRepository->shouldReceive('getLeagueMatches')
            ->with('B')
            ->andReturn(Collection::make([
                $this->createMatch('Hooligans FC', 'Wild Goats', 1, 0),
                $this->createMatch('Wild Goats', 'Hooligans FC', 2, 2),
            ]));

        $this->matchRepository->shouldReceive('generateSecondLegMatches')
            ->with('A')
            ->once();

        $this->matchRepository->shouldReceive('generateSecondLegMatches')
            ->with('B')
            ->once();

        $this->standingRepository->shouldReceive('findByTeam')
            ->with(Mockery::type(Team::class))
            ->andReturn(new Standing());

        $this->standingRepository->shouldReceive('updateStats')
            ->with(Mockery::type(Standing::class), Mockery::type('array'))
            ->andReturnNull();

        $this->artisan('cup:play-matches')
            ->expectsOutput('League A Matches:')
            ->expectsOutput('SmartView FC 2 - 0 Cornwall United')
            ->expectsOutput('Cornwall United 1 - 3 SmartView FC')
            ->expectsOutput('')
            ->expectsOutput('League B Matches:')
            ->expectsOutput('Hooligans FC 1 - 0 Wild Goats')
            ->expectsOutput('Wild Goats 2 - 2 Hooligans FC')
            ->assertExitCode(0);
    }

    public function test_full_tournament_simulation(): void
    {
        $smartViewTeam = new Team(['name' => 'SmartView FC', 'league' => 'A']);
        $cornwallTeam = new Team(['name' => 'Cornwall United', 'league' => 'A']);
        $hooligansTeam = new Team(['name' => 'Hooligans FC', 'league' => 'B']);
        $wildGoatsTeam = new Team(['name' => 'Wild Goats', 'league' => 'B']);

        $this->teamRepository->shouldReceive('getAllTeams')
            ->once()
            ->andReturn(Collection::make([
                $smartViewTeam,
                $cornwallTeam,
                $hooligansTeam,
                $wildGoatsTeam,
            ]));

        $this->standingRepository->shouldReceive('initializeTeamStanding')
            ->times(4)
            ->andReturn(new Standing());

        $this->matchRepository->shouldReceive('getLeagueMatches')
            ->with('A')
            ->andReturn(Collection::make([
                $this->createMatch('SmartView FC', 'Cornwall United', 2, 0),
                $this->createMatch('Cornwall United', 'SmartView FC', 1, 3),
            ]));

        $this->matchRepository->shouldReceive('getLeagueMatches')
            ->with('B')
            ->andReturn(Collection::make([
                $this->createMatch('Hooligans FC', 'Wild Goats', 1, 0),
                $this->createMatch('Wild Goats', 'Hooligans FC', 2, 2),
            ]));

        $this->matchRepository->shouldReceive('generateSecondLegMatches')
            ->with('A')
            ->once();

        $this->matchRepository->shouldReceive('generateSecondLegMatches')
            ->with('B')
            ->once();

        $this->standingRepository->shouldReceive('findByTeam')
            ->with(Mockery::type(Team::class))
            ->andReturn(new Standing());

        $this->standingRepository->shouldReceive('updateStats')
            ->with(Mockery::type(Standing::class), Mockery::type('array'))
            ->andReturnNull();

        $this->teamRepository->shouldReceive('getLeagueWinner')
            ->with('A')
            ->andReturn($smartViewTeam);

        $this->teamRepository->shouldReceive('getLeagueWinner')
            ->with('B')
            ->andReturn($hooligansTeam);

        $finalMatch = new GameMatch([
            'home_score' => 3,
            'away_score' => 1,
            'is_final' => true,
        ]);
        $finalMatch->homeTeam()->associate($smartViewTeam);
        $finalMatch->awayTeam()->associate($hooligansTeam);

        $this->matchRepository->shouldReceive('createFinalMatch')
            ->with($smartViewTeam, $hooligansTeam)
            ->andReturn($finalMatch);

        $this->artisan('cup:simulate')
            ->expectsOutput('League A Matches:')
            ->expectsOutput('SmartView FC 2 - 0 Cornwall United')
            ->expectsOutput('Cornwall United 1 - 3 SmartView FC')
            ->expectsOutput('')
            ->expectsOutput('League B Matches:')
            ->expectsOutput('Hooligans FC 1 - 0 Wild Goats')
            ->expectsOutput('Wild Goats 2 - 2 Hooligans FC')
            ->expectsOutput('')
            ->expectsOutput('Final Match:')
            ->expectsOutput('SmartView FC 3 - 1 Hooligans FC')
            ->expectsOutput('')
            ->expectsOutput('Tournament Winner: SmartView FC')
            ->assertExitCode(0);
    }

    private function createMatch(string $homeTeam, string $awayTeam, ?int $homeScore = null, ?int $awayScore = null): GameMatch
    {
        $match = new GameMatch([
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ]);

        $match->homeTeam()->associate(new Team(['name' => $homeTeam]));
        $match->awayTeam()->associate(new Team(['name' => $awayTeam]));

        return $match;
    }
}
