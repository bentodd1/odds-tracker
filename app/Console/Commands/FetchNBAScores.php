<?php

namespace App\Console\Commands;

use App\Services\OddsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchNBAScores extends Command
{
    protected $signature = 'scores:nba 
                          {start_date : Start date in Y-m-d format} 
                          {end_date? : Optional end date in Y-m-d format}';

    protected $description = 'Fetch NBA scores for a date or date range';

    protected OddsService $oddsService;

    public function __construct(OddsService $oddsService)
    {
        parent::__construct();
        $this->oddsService = $oddsService;
    }

    public function handle()
    {
        $startDate = Carbon::parse($this->argument('start_date'));
        $endDate = $this->argument('end_date') ? Carbon::parse($this->argument('end_date')) : $startDate;

        $this->info("Fetching scores from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        
        $totalGames = 0;
        $totalMatched = 0;
        $totalUnmatched = 0;
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $this->info("\nProcessing {$currentDate->format('Y-m-d')}");
            $result = $this->oddsService->getNBAScores($currentDate);

            if ($result['status'] === 'success') {
                $totalGames += count($result['data']);
                $totalMatched += $result['matched_count'];
                $totalUnmatched += $result['unmatched_count'];

                $this->info("Games found: " . count($result['data']));
                $this->info("Games matched: {$result['matched_count']}");
                $this->info("Games unmatched: {$result['unmatched_count']}");

                // Display all games with scores
                foreach ($result['data'] as $game) {
                    $this->line(
                        str_pad($game['away_team'], 20) . " " .
                        str_pad($game['away_score'], 5) . " @ " .
                        str_pad($game['home_team'], 20) . " " .
                        str_pad($game['home_score'], 5) . " (" . $game['status'] . ")"
                    );
                }

                // Display unmatched games
                if ($result['unmatched_count'] > 0) {
                    $this->warn("\nUnmatched games:");
                    foreach ($result['unmatched_games'] as $game) {
                        $this->warn("{$game['away_team']} @ {$game['home_team']}");
                    }
                }
            } else {
                $this->error("Error for {$currentDate->format('Y-m-d')}: {$result['message']}");
            }

            $currentDate->addDay();
        }

        $this->info("\nSummary:");
        $this->info("Total days processed: " . $startDate->diffInDays($endDate) + 1);
        $this->info("Total games found: $totalGames");
        $this->info("Total games matched: $totalMatched");
        $this->info("Total games unmatched: $totalUnmatched");
    }
} 