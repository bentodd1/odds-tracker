<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Game;
use App\Models\Score;
use App\Models\Sport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;
use App\Models\Team;

class MatchNCAABScores extends Command
{
    protected $signature = 'match:ncaab-scores
        {--date= : The date to fetch scores for (YYYY-MM-DD format)}
        {--debug : Show debug information}';
    protected $description = 'Match NCAA basketball scores from NCAA.com to existing games';

    private $unmatched = [];
    private $matched = [];
    private $sport;

    // Common team name variations to help with matching
    private $teamMappings = [
        // Common abbreviations
        'usc' => ['southern california', 'southern cal'],
        'ucf' => ['central florida', 'cent florida'],
        'uconn' => ['connecticut'],
        'umass' => ['massachusetts'],
        'smu' => ['southern methodist'],
        'tcu' => ['texas christian'],
        'ucla' => ['california los angeles'],
        'unlv' => ['nevada las vegas'],
        'utep' => ['texas el paso'],
        'unc' => ['north carolina'],
        'ole miss' => ['mississippi'],
        'nc state' => ['north carolina state'],

        // Saint/St. variations
        'saint johns' => ['st johns', 'st. johns', 'st. john\'s', 'st johns red storm'],
        'saint marys' => ['st marys', 'st. marys', 'saint marys gaels'],
        'saint louis' => ['st louis', 'st. louis', 'saint louis billikens'],
        'saint josephs' => ['st josephs', 'st. josephs', 'saint josephs hawks'],
        'saint peters' => ['st peters', 'st. peters'],
        'saint bonaventure' => ['st bonaventure', 'st. bonaventure'],
        'saint francis' => ['st francis', 'st. francis'],
        'saint thomas' => ['st thomas', 'st. thomas'],

        // Common suffixes to remove
        'blue devils' => ['duke'],
        'crimson tide' => ['alabama'],
        'volunteers' => ['tennessee'],
        'tigers' => ['memphis', 'missouri', 'auburn', 'clemson', 'lsu'],
        'wildcats' => ['kentucky', 'arizona', 'kansas state', 'villanova'],
        'bulldogs' => ['gonzaga', 'butler', 'georgia', 'mississippi state'],
        'cardinals' => ['louisville', 'stanford'],
        'bears' => ['baylor', 'california'],
        'hoyas' => ['georgetown'],
        'jayhawks' => ['kansas'],
        'tar heels' => ['north carolina'],
        'wolfpack' => ['nc state'],
        'orange' => ['syracuse'],
        'spartans' => ['michigan state'],
        'wolverines' => ['michigan'],
        'buckeyes' => ['ohio state'],
        'boilermakers' => ['purdue'],
        'hoosiers' => ['indiana'],
        'hawkeyes' => ['iowa'],
        'cyclones' => ['iowa state'],
        'razorbacks' => ['arkansas'],
        'gators' => ['florida'],
        'seminoles' => ['florida state'],
        'hurricanes' => ['miami fl', 'miami florida'],
        'cavaliers' => ['virginia'],
        'hokies' => ['virginia tech'],

        // State variations
        'mississippi state' => ['miss state', 'miss st'],
        'michigan state' => ['mich state', 'mich st'],
        'florida state' => ['fla state', 'fla st'],
        'kansas state' => ['kan state', 'kan st', 'k-state'],
        'arizona state' => ['asu', 'ariz state', 'ariz st'],
        'arkansas state' => ['ark state', 'ark st'],
        'louisiana state' => ['lsu', 'la state'],
        'ohio state' => ['osu'],
        'oklahoma state' => ['okla state', 'okla st', 'ok state'],
        'oregon state' => ['ore state', 'ore st'],

        // Directional schools
        'north carolina' => ['unc', 'n carolina', 'n.c.'],
        'south carolina' => ['s carolina', 's.c.'],
        'west virginia' => ['w virginia', 'w.v.'],
        'east carolina' => ['e carolina', 'e.c.'],
        'northern illinois' => ['n illinois', 'n. illinois'],
        'southern illinois' => ['s illinois', 's. illinois'],
        'western kentucky' => ['w kentucky', 'w. kentucky'],
        'eastern kentucky' => ['e kentucky', 'e. kentucky'],

        // Other common variations
        'miami fl' => ['miami florida', 'miami (fl)', 'miami (fla)'],
        'miami oh' => ['miami ohio', 'miami (oh)', 'miami (ohio)'],
        'texas am' => ['texas a&m', 'texas a & m'],
        'bowling green' => ['bowling green state', 'bgsu'],
        'brigham young' => ['byu'],
        'central michigan' => ['cent michigan', 'cmu'],
        'cincinnati' => ['cincy'],
        'colorado state' => ['colo state', 'colo st'],
        'depaul' => ['de paul'],
        'detroit mercy' => ['detroit'],
        'illinois chicago' => ['uic'],
        'loyola chicago' => ['loyola il', 'loyola (il)'],
        'loyola marymount' => ['lmu'],
        'massachusetts lowell' => ['umass lowell'],
        'middle tennessee' => ['middle tenn', 'mtsu'],
        'mississippi valley state' => ['mvsu'],
        'missouri kansas city' => ['umkc'],
        'montana state' => ['mont state', 'mont st'],
        'nevada las vegas' => ['unlv'],
        'new mexico state' => ['nm state', 'nmsu'],
        'north carolina at' => ['nc at', 'north carolina a&t'],
        'north carolina central' => ['nc central'],
        'north dakota state' => ['ndsu'],
        'south dakota state' => ['sdsu'],
    ];

    public function handle()
    {
        $this->sport = Sport::where('key', 'basketball_ncaab')->first();
        if (!$this->sport) {
            $this->error('Sport not found');
            return 1;
        }

        // Get target date from option or use today
        $targetDate = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', $this->option('date'))
            : Carbon::today();

        // First, fetch all NCAA games
        $ncaaGames = $this->fetchNcaaGames($targetDate);
        $this->info("Found " . count($ncaaGames) . " NCAA games for {$targetDate->format('Y-m-d')}");

        $newScores = 0;
        $updatedScores = 0;

        foreach ($ncaaGames as $ncaaGame) {
            // Record the score
            $result = $this->recordGameScore($ncaaGame['game'], [
                'date' => $targetDate->format('Y/m/d'),
                'home_score' => $ncaaGame['home_score'],
                'away_score' => $ncaaGame['away_score']
            ]);

            if ($result === 'created') {
                $newScores++;
            } elseif ($result === 'updated') {
                $updatedScores++;
            }
        }

        $this->info("\nScores processed:");
        $this->info("New scores: {$newScores}");
        $this->info("Updated scores: {$updatedScores}");

        return 0;
    }

    private function fetchNcaaGames($targetDate)
    {
        $url = "https://www.ncaa.com/scoreboard/basketball-men/d1/{$targetDate->format('Y/m/d')}/all-conf";
        $response = Http::get($url);

        if (!$response->successful()) {
            $this->error("Failed to fetch scores from NCAA.com");
            return [];
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($response->body());
        $xpath = new DOMXPath($doc);

        $games = [];
        $gameContainers = $xpath->query("//div[contains(@class, 'gamePod-type-game')]");

        foreach ($gameContainers as $container) {
            $teams = $xpath->query(".//ul[contains(@class, 'gamePod-game-teams')]/li", $container);

            if ($teams->length !== 2) {
                continue;
            }

            $homeTeamName = trim($xpath->query(".//span[contains(@class, 'gamePod-game-team-name')][not(contains(@class, 'short'))]", $teams->item(0))->item(0)->textContent);
            $awayTeamName = trim($xpath->query(".//span[contains(@class, 'gamePod-game-team-name')][not(contains(@class, 'short'))]", $teams->item(1))->item(0)->textContent);

            $homeScore = (int)trim($xpath->query(".//span[contains(@class, 'gamePod-game-team-score')]", $teams->item(0))->item(0)->textContent);
            $awayScore = (int)trim($xpath->query(".//span[contains(@class, 'gamePod-game-team-score')]", $teams->item(1))->item(0)->textContent);

            if (!$homeTeamName || !$awayTeamName || !$homeScore || !$awayScore) {
                continue;
            }

            $this->info("\nFound NCAA game: {$homeTeamName} ({$homeScore}) vs {$awayTeamName} ({$awayScore})");

            // Find matching teams
            $homeTeam = $this->findMatchingTeam($homeTeamName);
            $awayTeam = $this->findMatchingTeam($awayTeamName);

            if (!$homeTeam || !$awayTeam) {
                $this->error("Could not find matching teams in database");
                $this->info("Home team found: " . ($homeTeam ? "Yes" : "No"));
                $this->info("Away team found: " . ($awayTeam ? "Yes" : "No"));
                continue;
            }

            $this->info("Matched to: {$homeTeam->name} vs {$awayTeam->name}");

            // Search for game with expanded date range
            $startDate = $targetDate->copy()->startOfDay()->subHours(14);
            $endDate = $targetDate->copy()->endOfDay()->addHours(14);

            $this->info("Searching for game between {$startDate} and {$endDate}");

            $query = Game::where('sport_id', $this->sport->id)
                ->where(function($query) use ($homeTeam, $awayTeam) {
                    $query->where(function($q) use ($homeTeam, $awayTeam) {
                        $q->where('home_team_id', $homeTeam->id)
                          ->where('away_team_id', $awayTeam->id);
                    })->orWhere(function($q) use ($homeTeam, $awayTeam) {
                        $q->where('home_team_id', $awayTeam->id)
                          ->where('away_team_id', $homeTeam->id);
                    });
                })
                ->whereBetween('commence_time', [$startDate, $endDate]);

            $this->info("SQL Query: " . $query->toSql());
            $this->info("Bindings: " . json_encode($query->getBindings()));

            $game = $query->first();

            if (!$game) {
                // Check if any game exists between these teams regardless of date
                $anyGame = Game::where('sport_id', $this->sport->id)
                    ->where(function($query) use ($homeTeam, $awayTeam) {
                        $query->where(function($q) use ($homeTeam, $awayTeam) {
                            $q->where('home_team_id', $homeTeam->id)
                              ->where('away_team_id', $awayTeam->id);
                        })->orWhere(function($q) use ($homeTeam, $awayTeam) {
                            $q->where('home_team_id', $awayTeam->id)
                              ->where('away_team_id', $homeTeam->id);
                        });
                    })
                    ->first();

                if ($anyGame) {
                    $this->info("Found game but outside date range. Game time: " . $anyGame->commence_time);
                } else {
                    $this->error("No game found at all between these teams");
                }
                continue;
            }

            $this->info("Found matching game in database (ID: {$game->id})");

            $games[] = [
                'game' => $game,
                'date' => $targetDate->format('Y/m/d'),
                'home_score' => $homeScore,
                'away_score' => $awayScore
            ];
        }

        return $games;
    }

    private function getTeamMappings()
    {
        return [
            // Exact matches from NCAA.com to our database names
            'Princeton' => 'Princeton Tigers',
            'Penn' => 'Pennsylvania Quakers',
            'Southern California' => 'USC Trojans',
            'Purdue' => 'Purdue Boilermakers',
            'VCU' => 'VCU Rams',
            'Dayton' => 'Dayton Flyers',
            'St. John\'s (NY)' => 'St. John\'s Red Storm',
            'UConn' => 'Connecticut Huskies',
            'Saint Louis' => 'Saint Louis Billikens',
            'Saint Joseph\'s' => 'Saint Joseph\'s Hawks',
            'San Jose St.' => 'San Jose State Spartans',
            'Boise St.' => 'Boise State Broncos',
            'Utah St.' => 'Utah State Aggies',
            'Fresno St.' => 'Fresno State Bulldogs',
            'Georgia' => 'Georgia Bulldogs',
            'LSU' => 'LSU Tigers'
        ];
    }

    private function findMatchingTeam($ncaaTeamName)
    {
        $mappings = $this->getTeamMappings();

        // If we have a direct mapping, use it
        $dbTeamName = $mappings[$ncaaTeamName] ?? null;

        if ($dbTeamName) {
            return Team::where('name', $dbTeamName)
                      ->where('sport_id', $this->sport->id)
                      ->first();
        }

        return null;
    }

    private function recordGameScore($game, $score)
    {
        try {
            $this->log("\nRecording score for game:");
            $this->log("Game ID: " . $game->id);
            $this->log("Teams: " . $game->homeTeam->name . " vs " . $game->awayTeam->name);
            
            $homeFpi = $game->homeTeam->latestFpi()->first();
            $awayFpi = $game->awayTeam->latestFpi()->first();

            $this->log("Home FPI: " . ($homeFpi ? $homeFpi->rating : 'null'));
            $this->log("Away FPI: " . ($awayFpi ? $awayFpi->rating : 'null'));

            $scoreData = [
                'game_id' => $game->id,
                'period' => 'F',  // Final score
                'home_score' => $score['home_score'],
                'away_score' => $score['away_score'],
                'home_fpi' => $homeFpi ? $homeFpi->rating : null,
                'away_fpi' => $awayFpi ? $awayFpi->rating : null,
                'date' => Carbon::createFromFormat('Y/m/d', $score['date'])->startOfDay(),
            ];

            $this->log("Score data to be saved:");
            $this->log(json_encode($scoreData, JSON_PRETTY_PRINT));

            $exists = Score::where([
                'game_id' => $game->id,
                'period' => 'F'
            ])->exists();

            $result = Score::updateOrCreate(
                [
                    'game_id' => $game->id,
                    'period' => 'F'
                ],
                $scoreData
            );

            $this->log("Score saved successfully: " . $result->id);
            
            return $exists ? 'updated' : 'created';

        } catch (\Exception $e) {
            $this->log("Error recording score: " . $e->getMessage(), 'error');
            $this->log("Stack trace: " . $e->getTraceAsString(), 'error');
            return null;
        }
    }

    private function log($message, $type = 'info')
    {
        // Console output
        if ($this->option('debug')) {
            if ($type === 'error') {
                $this->error($message);
            } else {
                $this->info($message);
            }
        }

        // File logging - ensure the logs directory exists
        $date = now()->format('Y-m-d');
        $logPath = storage_path("logs/ncaab-scores-{$date}.log");

        // Create logs directory if it doesn't exist
        if (!File::exists(dirname($logPath))) {
            File::makeDirectory(dirname($logPath), 0755, true);
        }

        $timestamp = now()->format('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$type}: {$message}\n";

        File::append($logPath, $logMessage);
    }
}
