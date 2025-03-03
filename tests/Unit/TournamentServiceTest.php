<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Standing;
use App\Models\Team;
use App\Repositories\GameMatchRepository;
use App\Repositories\StandingRepository;
use App\Repositories\TeamRepository;
use App\Services\TournamentService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Services\TournamentService
 */
class TournamentServiceTest extends TestCase
{
    private const TEST_LEAGUE_A = 'A';
    private const TEST_LEAGUE_B = 'B';

    private TournamentService $service;
    private TeamRepository $teamRepository;
    private GameMatchRepository $matchRepository;
    private StandingRepository $standingRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teamRepository = Mockery::mock(TeamRepository::class);
        $this->matchRepository = Mockery::mock(GameMatchRepository::class);
        $this->standingRepository = Mockery::mock(StandingRepository::class);

        $this->service = new TournamentService(
            $this->teamRepository,
            $this->matchRepository,
            $this->standingRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @covers ::playLeagueMatches
     */
    public function test_all_matches_are_created_with_correct_scores(): void
    {
        $firstLegMatches = Collection::make([
            $this->createMatch('SmartView FC', 'Cornwall United', 2, 0),
            $this->createMatch('Hooligans FC', 'Wild Goats', 1, 0),
        ]);

        $secondLegMatches = Collection::make([
            $this->createMatch('Cornwall United', 'SmartView FC', 1, 3),
            $this->createMatch('Wild Goats', 'Hooligans FC', 2, 2),
        ]);

        $allMatches = $firstLegMatches->merge($secondLegMatches);

        $this->teamRepository->shouldReceive('getAllTeams')
            ->once()
            ->andReturn(Collection::make([
                new Team(['name' => 'SmartView FC']),
                new Team(['name' => 'Cornwall United']),
                new Team(['name' => 'Hooligans FC']),
                new Team(['name' => 'Wild Goats']),
            ]));

        $this->standingRepository->shouldReceive('initializeTeamStanding')
            ->times(4)
            ->andReturn(new Standing());

        $this->matchRepository->shouldReceive('getLeagueMatches')
            ->with(self::TEST_LEAGUE_A)
            ->andReturn($allMatches);

        $this->matchRepository->shouldReceive('getLeagueMatches')
            ->with(self::TEST_LEAGUE_B)
            ->andReturn(new Collection());

        $this->matchRepository->shouldReceive('generateSecondLegMatches')
            ->with(self::TEST_LEAGUE_A)
            ->once();

        $this->matchRepository->shouldReceive('generateSecondLegMatches')
            ->with(self::TEST_LEAGUE_B)
            ->once();

        $this->standingRepository->shouldReceive('findByTeam')
            ->with(Mockery::type(Team::class))
            ->andReturn(new Standing());

        $this->standingRepository->shouldReceive('updateStats')
            ->with(Mockery::type(Standing::class), Mockery::type('array'))
            ->andReturnNull();

        $this->service->playLeagueMatches();

        $this->assertEquals(2, $firstLegMatches[0]->home_score);
        $this->assertEquals(0, $firstLegMatches[0]->away_score);
        $this->assertEquals(1, $firstLegMatches[1]->home_score);
        $this->assertEquals(0, $firstLegMatches[1]->away_score);

        $this->assertEquals(1, $secondLegMatches[0]->home_score);
        $this->assertEquals(3, $secondLegMatches[0]->away_score);
        $this->assertEquals(2, $secondLegMatches[1]->home_score);
        $this->assertEquals(2, $secondLegMatches[1]->away_score);
    }

    /**
     * @covers ::playLeagueMatches
     */
    public function test_standings_are_calculated_correctly(): void
    {
        $smartViewTeam = new Team(['name' => 'SmartView FC', 'league' => self::TEST_LEAGUE_A]);
        $standing = new Standing([
            'played' => 8,
            'won' => 5,
            'drawn' => 2,
            'lost' => 1,
            'goals_for' => 14,
            'goals_against' => 6,
            'points' => 17,
        ]);
        $standing->team()->associate($smartViewTeam);

        $this->teamRepository->shouldReceive('getAllTeams')
            ->once()
            ->andReturn(Collection::make([$smartViewTeam]));

        $this->standingRepository->shouldReceive('initializeTeamStanding')
            ->once()
            ->andReturn($standing);

        $this->matchRepository->shouldReceive('getLeagueMatches')
            ->twice()
            ->andReturn(new Collection());

        $this->matchRepository->shouldReceive('generateSecondLegMatches')
            ->with(self::TEST_LEAGUE_A)
            ->once();

        $this->matchRepository->shouldReceive('generateSecondLegMatches')
            ->with(self::TEST_LEAGUE_B)
            ->once();

        $this->standingRepository->shouldReceive('findByTeam')
            ->with(Mockery::type(Team::class))
            ->andReturn($standing);

        $this->standingRepository->shouldReceive('updateStats')
            ->with(Mockery::type(Standing::class), Mockery::type('array'))
            ->andReturnNull();

        $this->service->playLeagueMatches();

        $this->assertEquals(8, $standing->played);
        $this->assertEquals(5, $standing->won);
        $this->assertEquals(2, $standing->drawn);
        $this->assertEquals(1, $standing->lost);
        $this->assertEquals(14, $standing->goals_for);
        $this->assertEquals(6, $standing->goals_against);
        $this->assertEquals(17, $standing->points);
    }

    /**
     * @covers ::getLeagueStandings
     */
    public function test_get_league_standings_sorts_correctly(): void
    {
        $smartViewTeam = new Team(['name' => 'SmartView FC', 'league' => self::TEST_LEAGUE_A]);
        $standing = new Standing(['points' => 17]);
        $standing->team()->associate($smartViewTeam);
        $standings = Collection::make([$standing]);

        $this->standingRepository->shouldReceive('findByLeague')
            ->with(self::TEST_LEAGUE_A)
            ->andReturn($standings);

        $this->standingRepository->shouldReceive('sortByRanking')
            ->with($standings)
            ->andReturn($standings);

        $result = $this->service->getLeagueStandings(self::TEST_LEAGUE_A);
        $this->assertEquals('SmartView FC', $result->first()->team->name);
        $this->assertEquals(17, $result->first()->points);
    }

    /**
     * @covers ::playFinalMatch
     */
    public function test_final_match_has_winner(): void
    {
        $leagueAWinner = new Team(['name' => 'SmartView FC', 'league' => self::TEST_LEAGUE_A]);
        $leagueBWinner = new Team(['name' => 'Berlinger Kickers', 'league' => self::TEST_LEAGUE_B]);
        $finalMatch = new GameMatch([
            'home_score' => 3,
            'away_score' => 1,
            'is_final' => true,
        ]);
        $finalMatch->homeTeam()->associate($leagueAWinner);
        $finalMatch->awayTeam()->associate($leagueBWinner);

        $this->teamRepository->shouldReceive('getLeagueWinner')
            ->with(self::TEST_LEAGUE_A)
            ->andReturn($leagueAWinner);

        $this->teamRepository->shouldReceive('getLeagueWinner')
            ->with(self::TEST_LEAGUE_B)
            ->andReturn($leagueBWinner);

        $this->matchRepository->shouldReceive('createFinalMatch')
            ->with($leagueAWinner, $leagueBWinner)
            ->andReturn($finalMatch);

        $result = $this->service->playFinalMatch();
        $this->assertTrue($result->is_final);
        $this->assertNotNull($result->getWinner());
        $this->assertNotEquals($result->home_score, $result->away_score);
        $this->assertEquals('SmartView FC', $result->homeTeam->name);
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
