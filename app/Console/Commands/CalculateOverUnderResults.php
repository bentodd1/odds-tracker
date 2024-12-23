<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\OverUnder;
use App\Models\OverUnderResult;
use App\Models\Sport;
use App\Models\Casino;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateOverUnderResults extends Command
{
    protected $signature = 'odds:calculate-overunder-results
                            {--sport= : The sport key to calculate results for}
                            {--casino= : The casino name to calculate results for}';

    protected $description = 'Calculate results for over/under bets on completed games';

    public function handle()
    {
        $query = OverUnder::query()
            ->whereDoesntHave('result')
            ->whereHas('game', function ($query) {
                $query->where('completed', true);
            })
            ->with(['game.scores', 'casino']);

        if ($sportKey = $this->option('sport')) {
            $sport = Sport::where('key', $sportKey)->first();

            if (!$sport) {
                $this->error("Sport not found: {$sportKey}");
                return 1;
            }

            $query->whereHas('game', function ($q) use ($sport) {
                $q->where('sport_id', $sport->id);
            });

            $this->info("Filtering for sport: {$sport->title}");
        }

        if ($casinoName = $this->option('casino')) {
            $casino = Casino::where('name', $casinoName)->first();

            if (!$casino) {
                $this->error("Casino not found: {$casinoName}");
                return 1;
            }

            $query->where('casino_id', $casino->id);
            $this->info("Filtering for casino: {$casino->name}");
        }

        $overUnders = $query->get();

        $this->info("Found {$overUnders->count()} unprocessed over/unders");

        $processed = 0;
        $errors = 0;

        foreach ($overUnders as $overUnder) {
            $this->info("\nProcessing over/under ID: {$overUnder->id} for game {$overUnder->game_id}");

            try {
                DB::beginTransaction();

                $score = $overUnder->game->scores->first();

                if (!$score) {
                    $this->warn("No scores found for game {$overUnder->game_id}");
                    DB::rollBack();
                    $errors++;
                    continue;
                }

                $totalPoints = $score->home_score + $score->away_score;

                $this->info("Home Score: {$score->home_score}");
                $this->info("Away Score: {$score->away_score}");
                $this->info("Total Points: {$totalPoints}, Line: {$overUnder->total}");

                $result = OverUnderResult::calculateResult($totalPoints, $overUnder->total);

                $overUnderResult = new OverUnderResult([
                    'over_under_id' => $overUnder->id,
                    'score_id' => $score->id,
                    'total_points' => $result['total_points'],
                    'result' => $result['result']
                ]);

                $overUnderResult->save();

                DB::commit();
                $processed++;

                $this->info("Result recorded: " . strtoupper($result['result']));

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error processing over/under {$overUnder->id}: " . $e->getMessage());
                $this->error("Stack trace: " . $e->getTraceAsString());
                $errors++;
            }
        }

        $this->info("\nProcessing completed:");
        $this->info("Successful: {$processed}");
        $this->info("Errors: {$errors}");

        if ($processed > 0) {
            $results = OverUnderResult::whereIn('over_under_id', $overUnders->pluck('id'))
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN result = "over" THEN 1 ELSE 0 END) as overs,
                    SUM(CASE WHEN result = "under" THEN 1 ELSE 0 END) as unders,
                    SUM(CASE WHEN result = "push" THEN 1 ELSE 0 END) as pushes
                ')
                ->first();

            $this->info("\nResults Summary:");
            $this->info("Overs: {$results->overs}");
            $this->info("Unders: {$results->unders}");
            $this->info("Pushes: {$results->pushes}");
        }

        return $errors > 0 ? 1 : 0;
    }
}
