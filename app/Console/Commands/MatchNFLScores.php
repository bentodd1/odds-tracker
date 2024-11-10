<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use League\Csv\Reader;
use App\Models\Game;
use App\Models\Score;
use App\Models\Sport;
use Carbon\Carbon;

class MatchNFLScores extends Command
{
    protected $signature = 'nfl:match-scores {--debug : Output debug information}';
    protected $description = 'Match NFL games with scores from nflverse data';

    private $teamMappings = [
        // Official names to abbreviations
        'Arizona Cardinals' => 'ARI',
        'Atlanta Falcons' => 'ATL',
        'Baltimore Ravens' => 'BAL',
        'Buffalo Bills' => 'BUF',
        'Carolina Panthers' => 'CAR',
        'Chicago Bears' => 'CHI',
        'Cincinnati Bengals' => 'CIN',
        'Cleveland Browns' => 'CLE',
        'Dallas Cowboys' => 'DAL',
        'Denver Broncos' => 'DEN',
        'Detroit Lions' => 'DET',
        'Green Bay Packers' => 'GB',
        'Houston Texans' => 'HOU',
        'Indianapolis Colts' => 'IND',
        'Jacksonville Jaguars' => 'JAX',
        'Kansas City Chiefs' => 'KC',
        'Las Vegas Raiders' => 'LV',
        'Los Angeles Chargers' => 'LAC',
        'Los Angeles Rams' => 'LA',
        'Miami Dolphins' => 'MIA',
        'Minnesota Vikings' => 'MIN',
        'New England Patriots' => 'NE',
        'New Orleans Saints' => 'NO',
        'New York Giants' => 'NYG',
        'New York Jets' => 'NYJ',
        'Philadelphia Eagles' => 'PHI',
        'Pittsburgh Steelers' => 'PIT',
        'San Francisco 49ers' => 'SF',
        'Seattle Seahawks' => 'SEA',
        'Tampa Bay Buccaneers' => 'TB',
        'Tennessee Titans' => 'TEN',
        'Washington Commanders' => 'WAS',

        // Common variations
        'JAC' => 'JAX',
        'JAX' => 'JAX',
        'Jacksonville' => 'JAX',
        'Jaguars' => 'JAX',
        'Jacksonville Jags' => 'JAX',
        'Washington' => 'WAS',
        'Washington Football Team' => 'WAS',
        'Washington Redskins' => 'WAS',
        'Raiders' => 'LV',
        'Oakland Raiders' => 'LV',
        'San Diego Chargers' => 'LAC',
        'St. Louis Rams' => 'LA',
    ];

    public function handle()
    {
        $csvUrl = 'https://raw.githubusercontent.com/nflverse/nfldata/master/data/games.csv';

        $response = Http::get($csvUrl);
        if (!$response->successful()) {
            $this->error('Failed to fetch the CSV file.');
            return 1;
        }

        $sport = Sport::where('key', 'americanfootball_nfl')->first();
        if (!$sport) {
            $this->error("NFL sport not found in database");
            return 1;
        }

        // Get all games since 2018, including playoffs
        $records = collect($this->csvToArray($response->body()))
            ->filter(function ($record) {
                $gameDate = Carbon::parse($record['gameday']);
                return $record
                    && $gameDate->year >= 2018
                    && $gameDate->lt(now()); // Only past games
            })
            ->values()
            ->all();

        if ($this->option('debug')) {
            $this->info("Fetched " . count($records) . " NFL game records");
        }

        // Get unmatched games that have started
        $games = Game::with(['homeTeam', 'awayTeam'])
            ->where('sport_id', $sport->id)
            ->where('commence_time', '<', now())
            ->whereDoesntHave('scores', function($q) {
                $q->where('period', 'F');
            })
            ->get();

        if ($this->option('debug')) {
            $this->info("Found {$games->count()} unmatched NFL games");
        }

        $matchedCount = 0;
        foreach ($games as $game) {
            if ($this->matchAndProcessGame($game, $records)) {
                $matchedCount++;
            }
        }

        $this->info("Successfully matched {$matchedCount} games");
    }
    private function matchAndProcessGame($game, $records)
    {
        if ($this->option('debug')) {
            $this->info("Looking for match for: {$game->homeTeam->name} vs {$game->awayTeam->name} on {$game->commence_time}");
        }

        $matchedRecord = collect($records)->first(function ($record) use ($game) {
            // Parse the game date from CSV
            $csvDate = Carbon::parse($record['gameday']);
            $gameDate = Carbon::parse($game->commence_time);

            // Check if dates are within 36 hours of each other
            $hoursDiff = abs($gameDate->diffInHours($csvDate));

            if ($hoursDiff > 36) {
                if ($this->option('debug')) {
                    $this->line("Skipping - time difference too large: {$hoursDiff} hours");
                }
                return false;
            }

            $homeTeam = $this->normalizeTeamName($record['home_team']);
            $awayTeam = $this->normalizeTeamName($record['away_team']);
            $dbHomeTeam = $this->normalizeTeamName($game->homeTeam->name);
            $dbAwayTeam = $this->normalizeTeamName($game->awayTeam->name);

            $teamsMatch = ($homeTeam === $dbHomeTeam && $awayTeam === $dbAwayTeam) ||
                ($homeTeam === $dbAwayTeam && $awayTeam === $dbHomeTeam);

            if ($this->option('debug') && $teamsMatch) {
                $this->info("Found potential match:");
                $this->line("DB Game: {$game->commence_time}");
                $this->line("CSV Game: {$record['gameday']}");
                $this->line("Hours difference: {$hoursDiff}");
            }

            return $teamsMatch;
        });

        if ($matchedRecord) {
            try {
                Score::create([
                    'game_id' => $game->id,
                    'home_score' => $matchedRecord['home_score'],
                    'away_score' => $matchedRecord['away_score'],
                    'period' => 'F'
                ]);

                if ($this->option('debug')) {
                    $this->info("✓ Created score for: {$game->homeTeam->name} vs {$game->awayTeam->name}");
                    $this->info("  Score: {$matchedRecord['home_score']} - {$matchedRecord['away_score']}");
                }

                return true;
            } catch (\Exception $e) {
                $this->error("Error creating score for game {$game->id}: " . $e->getMessage());
                return false;
            }
        } elseif ($this->option('debug')) {
            $this->warn("✗ No match found for: {$game->homeTeam->name} vs {$game->awayTeam->name}");
        }

        return false;
    }

    private function normalizeTeamName($name)
    {
        // First, clean the name
        $name = trim($name);

        // Direct lookup in mappings
        if (isset($this->teamMappings[$name])) {
            return $this->teamMappings[$name];
        }

        // Try case-insensitive lookup
        foreach ($this->teamMappings as $teamName => $abbrev) {
            if (strcasecmp($teamName, $name) === 0) {
                return $abbrev;
            }
        }

        // If it's already an abbreviation, return it
        if (strlen($name) <= 3) {
            return strtoupper($name);
        }

        if ($this->option('debug')) {
            $this->warn("Could not normalize team name: {$name}");
        }

        return strtoupper(preg_replace('/[^A-Z]/', '', strtoupper($name)));
    }
    private function csvToArray($csv)
    {
        $lines = explode(PHP_EOL, trim($csv));
        $headers = str_getcsv(array_shift($lines));

        if ($this->option('debug')) {
            $this->info("CSV Headers: " . implode(', ', $headers));
        }

        return array_map(function($line) use ($headers) {
            $row = str_getcsv($line);
            if (count($row) === count($headers)) {
                $data = array_combine($headers, $row);

                // Map the NFL data columns to our expected format
                return [
                    'gameday' => $data['gameday'] . ' ' . $data['gametime'],  // Combine date and time
                    'home_team' => $data['home_team'],
                    'away_team' => $data['away_team'],
                    'home_score' => $data['home_score'],
                    'away_score' => $data['away_score']
                ];
            }
            return null;
        }, array_filter($lines));
    }
}
