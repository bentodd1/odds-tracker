<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Spread;
use App\Models\SpreadResult;
use Illuminate\Support\Facades\DB;

class CalculateSpreadResults extends Command
{
    protected $signature = 'spreads:calculate-results {--debug : Show debug information}';
    protected $description = 'Calculate results for all spreads that have final scores';

    public function handle()
    {
        $processedCount = 0;
        $chunkSize = 100; // Process 100 spreads at a time

        // Get total count for progress bar
        $totalSpreads = Spread::query()
            ->join('games', 'spreads.game_id', '=', 'games.id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('scores')
                    ->whereColumn('scores.game_id', 'games.id')
                    ->where('scores.period', 'F');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('spread_results')
                    ->whereColumn('spread_results.spread_id', 'spreads.id');
            })
            ->count();

        if ($this->option('debug')) {
            $this->info("Found {$totalSpreads} spreads to process");
        }

        $bar = $this->output->createProgressBar($totalSpreads);
        $bar->start();

        Spread::query()
            ->select('spreads.*')
            ->join('games', 'spreads.game_id', '=', 'games.id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('scores')
                    ->whereColumn('scores.game_id', 'games.id')
                    ->where('scores.period', 'F');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('spread_results')
                    ->whereColumn('spread_results.spread_id', 'spreads.id');
            })
            ->with(['game.scores' => function($query) {
                $query->where('period', 'F');
            }])
            ->chunk($chunkSize, function ($spreads) use (&$processedCount, $bar, $chunkSize) {  // Added $chunkSize here
                foreach ($spreads as $spread) {
                    try {
                        $score = $spread->game->scores->first();

                        if (!$score) {
                            if ($this->option('debug')) {
                                $this->newLine();
                                $this->warn("No final score found for game {$spread->game_id}");
                            }
                            continue;
                        }

                        SpreadResult::createFromScore($score, $spread);
                        $processedCount++;
                        $bar->advance();

                        if ($this->option('debug')) {
                            $this->newLine();
                            $this->info("Processed spread {$spread->id}: " .
                                "{$spread->game->homeTeam->name} ({$score->home_score}) vs " .
                                "{$spread->game->awayTeam->name} ({$score->away_score}), " .
                                "Spread: {$spread->spread}");
                        }

                    } catch (\Exception $e) {
                        $this->newLine();
                        $this->error("Error processing spread {$spread->id}: " . $e->getMessage());
                    }
                }

                if ($this->option('debug')) {
                    $this->newLine();
                    $this->info("Processed chunk of {$chunkSize} spreads");
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info("Processed {$processedCount} spread results");

        // Show summary if debug is enabled
        if ($this->option('debug')) {
            $results = SpreadResult::select('result', DB::raw('count(*) as count'))
                ->groupBy('result')
                ->get();

            $this->info("\nResults Summary:");
            foreach ($results as $result) {
                $this->line("{$result->result}: {$result->count}");
            }
        }
    }
}
