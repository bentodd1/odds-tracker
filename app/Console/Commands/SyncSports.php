<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OddsApiService;
use App\Models\Sport;

class SyncSports extends Command
{
    protected $signature = 'odds:sync-sports';
    protected $description = 'Synchronize sports from the Odds API';

    public function handle(OddsApiService $oddsApi)
    {
        $this->info('Fetching sports from Odds API...');

        try {
            $sports = $oddsApi->getSports();
            $count = 0;

            foreach ($sports as $sport) {
                Sport::updateOrCreate(
                    ['key' => $sport['key']],
                    [
                        'group' => $sport['group'],
                        'title' => $sport['title'],
                        'active' => true
                    ]
                );
                $count++;
            }

            $this->info("Successfully synchronized {$count} sports.");
        } catch (\Exception $e) {
            $this->error('Error synchronizing sports: ' . $e->getMessage());
        }
    }
}
