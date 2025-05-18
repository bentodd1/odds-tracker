<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Game;
use App\Models\Team;
use App\Models\Score;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\MoneyLine;

class SyncMlbScores extends Command
{
    protected $signature = 'mlb:sync-scores
        {--start-date=2023-03-30 : Start date (YYYY-MM-DD)}
        {--end-date=2023-10-01 : End date (YYYY-MM-DD)}
        {--debug : Show debug output}';

    protected $description = 'Sync MLB final scores from the MLB API and update local games';

    public function handle()
    {
        $startDate = Carbon::parse($this->option('start-date'));
        $endDate = Carbon::parse($this->option('end-date'));
        $debug = $this->option('debug');

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $this->info("Fetching MLB games for {$dateString}");

            $url = "https://statsapi.mlb.com/api/v1/schedule?sportId=1&date={$dateString}";
            $response = Http::get($url);

            if ($response->failed()) {
                $this->error("Failed to fetch MLB API for {$dateString}");
                $currentDate->addDay();
                continue;
            }

            $data = $response->json();
            $games = $data['dates'][0]['games'] ?? [];

            foreach ($games as $mlbGame) {
                $homeTeamName = $mlbGame['teams']['home']['team']['name'];
                $awayTeamName = $mlbGame['teams']['away']['team']['name'];
                $homeScore = $mlbGame['teams']['home']['score'] ?? null;
                $awayScore = $mlbGame['teams']['away']['score'] ?? null;
                $gameDate = Carbon::parse($mlbGame['gameDate']);

                // Check if the game is final/completed and scores are present
                $status = $mlbGame['status']['detailedState'] ?? '';
                if ($homeScore === null || $awayScore === null || strtolower($status) !== 'final') {
                    // Skip games that did not happen or are not completed
                    continue;
                }

                // Find local teams
                $homeTeam = Team::where('name', $homeTeamName)->first();
                $awayTeam = Team::where('name', $awayTeamName)->first();

                if (!$homeTeam || !$awayTeam) {
                    $this->warn("No local team match for: {$awayTeamName} at {$homeTeamName}");
                    continue;
                }

                // Find the local game record by teams and commence_time (with a ±12 hour window)
                $game = Game::where('home_team_id', $homeTeam->id)
                    ->where('away_team_id', $awayTeam->id)
                    ->whereBetween('commence_time', [
                        $gameDate->copy()->subHours(12),
                        $gameDate->copy()->addHours(12)
                    ])
                    ->first();

                if ($game) {
                    // Update or create the Score record
                    Score::updateOrCreate(
                        ['game_id' => $game->id],
                        [
                            'home_score' => $homeScore,
                            'away_score' => $awayScore,
                            'recorded_at' => $gameDate,
                        ]
                    );

                    $game->completed = true;
                    $game->save();

                    if ($debug) {
                        $this->info("Updated: {$awayTeamName} at {$homeTeamName} ({$awayScore}-{$homeScore})");
                    }

                    if ($homeOutcome && $awayOutcome) {
                        MoneyLine::create([
                            'game_id' => $game->id,
                            'casino_id' => $casino->id,
                            'home_odds' => $homeOutcome['price'],
                            'away_odds' => $awayOutcome['price'],
                            'recorded_at' => $timestamp,
                        ]);
                        if ($this->debug) {
                            $this->info("Saved money line for game {$game->id} at casino {$casino->name} ({$homeOutcome['price']}/{$awayOutcome['price']})");
                        }
                    } else {
                        if ($this->debug) {
                            $this->warn("No valid money line outcome for game {$game->id} at casino {$casino->name}");
                        }
                    }
                } else {
                    $this->warn("No local game match for: {$awayTeamName} at {$homeTeamName} on {$gameDate}");
                }
            }

            $currentDate->addDay();
        }

        $this->info("MLB scores sync complete.");
    }
} 