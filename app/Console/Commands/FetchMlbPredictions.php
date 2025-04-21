<?php

namespace App\Console\Commands;

use App\Services\DratingsService;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Game;
use App\Models\Sport;
use App\Models\DratingsPrediction;

class FetchMlbPredictions extends Command
{
    protected $signature = 'dratings:fetch-mlb {--debug : Show debug information}';
    protected $description = 'Fetch MLB predictions from Dratings';

    protected $dratingsService;

    public function __construct(DratingsService $dratingsService)
    {
        parent::__construct();
        $this->dratingsService = $dratingsService;
    }

    public function handle()
    {
        try {
            $service = new DratingsService();
            $games = $service->getMlbPredictions();

            if (empty($games)) {
                $this->error('No games found');
                return Command::FAILURE;
            }

            $this->info('Found ' . count($games) . ' games:');
            
            foreach ($games as $gameData) {
                $game = $gameData['game'];
                $prediction = $gameData['prediction'];
                
                $this->line(sprintf(
                    "%s @ %s - %s vs %s",
                    $game['awayTeam'],
                    $game['homeTeam'],
                    $prediction['awayProbability'] . '%',
                    $prediction['homeProbability'] . '%'
                ));

                // Save the prediction to the database
                try {
                    $gameModel = Game::where('sport_id', Sport::where('key', 'baseball_mlb')->first()->id)
                        ->where(function($query) use ($game) {
                            $query->where(function($q) use ($game) {
                                $q->whereHas('homeTeam', function($q) use ($game) {
                                    $q->where('name', 'like', '%' . $game['homeTeam'] . '%');
                                })->whereHas('awayTeam', function($q) use ($game) {
                                    $q->where('name', 'like', '%' . $game['awayTeam'] . '%');
                                });
                            });
                        })
                        ->where('commence_time', '>=', Carbon::parse($game['startTime'])->subHours(12))
                        ->where('commence_time', '<=', Carbon::parse($game['startTime'])->addHours(12))
                        ->first();

                    if ($gameModel) {
                        DratingsPrediction::create([
                            'game_id' => $gameModel->id,
                            'home_win_probability' => $prediction['homeProbability'],
                            'away_win_probability' => $prediction['awayProbability'],
                            'home_moneyline' => $prediction['homeOdds'],
                            'away_moneyline' => $prediction['awayOdds'],
                            'recorded_at' => now()
                        ]);
                        
                        if ($this->option('debug')) {
                            $this->info("Saved prediction for game ID: " . $gameModel->id);
                        }
                    } else {
                        if ($this->option('debug')) {
                            $this->warn("Could not find matching game for: {$game['awayTeam']} @ {$game['homeTeam']}");
                        }
                    }
                } catch (\Exception $e) {
                    if ($this->option('debug')) {
                        $this->error("Error saving prediction: " . $e->getMessage());
                    }
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            if ($this->option('debug')) {
                $this->error($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }
}
