<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\FpiRating;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;

class FetchCollegeFpi extends Command
{
    protected $signature = 'espn:fetch-college-fpi {--debug : Show debug information}';
    protected $description = 'Fetch FPI ratings from ESPN for college football teams';

    public function handle()
    {
        $this->info('Fetching ESPN College Football FPI ratings...');
        $debug = $this->option('debug');

        try {
            $response = Http::get('https://www.espn.com/college-football/fpi');
            $html = $response->body();

            if ($debug) {
                file_put_contents(storage_path('app/debug_college.html'), $html);
                $this->info("Saved ESPN response to storage/app/debug_college.html");
            }

            $doc = new DOMDocument();
            @$doc->loadHTML($html);
            $xpath = new DOMXPath($doc);

            // Get teams from the fixed left table
            $teamRows = $xpath->query("//table[contains(@class, 'Table--fixed-left')]//tr[contains(@class, 'Table__TR--sm')]");

            // Get FPI data from the scrollable table
            $dataRows = $xpath->query("//div[contains(@class, 'Table__ScrollerWrapper')]//tr[contains(@class, 'Table__TR--sm')]");

            if ($debug) {
                $this->info("Found " . $teamRows->length . " team rows and " . $dataRows->length . " data rows");
            }

            $timestamp = now();
            $count = 0;
            $latestRevision = FpiRating::max('revision') ?? 0;
            $newRevision = $latestRevision + 1;

            foreach ($teamRows as $index => $teamRow) {
                // Get team name from the first table
                $teamNode = $xpath->query(".//span[contains(@class, 'TeamLink__Name')]//a", $teamRow)->item(0);
                if (!$teamNode) {
                    if ($debug) {
                        $this->warn("No team found in row {$index}");
                    }
                    continue;
                }

                $teamName = trim($teamNode->textContent);

                // Get corresponding data row
                $dataRow = $dataRows->item($index);
                if (!$dataRow) {
                    if ($debug) {
                        $this->warn("No data row found for {$teamName}");
                    }
                    continue;
                }

                // Get cells from data row
                $cells = $xpath->query(".//td[contains(@class, 'Table__TD')]", $dataRow);

                if ($debug) {
                    for ($i = 0; $i < $cells->length; $i++) {
                        $this->info("Cell {$i} content for {$teamName}: " . trim($cells->item($i)->textContent));
                    }
                }

                // FPI is the second cell (index 1) in the data table
                $fpiCell = $cells->item(1);
                if (!$fpiCell) {
                    if ($debug) {
                        $this->warn("No FPI cell found for {$teamName}");
                    }
                    continue;
                }

                $fpiValue = trim($fpiCell->textContent);
                if (!is_numeric($fpiValue)) {
                    if ($debug) {
                        $this->warn("Invalid FPI value for {$teamName}: {$fpiValue}");
                    }
                    continue;
                }

                // Find team in database with NCAAF sport filter
                $team = Team::whereHas('sport', function($query) {
                    $query->where('title', 'NCAAF');
                })
                ->where(function ($query) use ($teamName) {
                    $query->where('name', $teamName)
                        ->orWhere('name', 'LIKE', "%{$teamName}%");
                })->first();

                if (!$team) {
                    if ($debug) {
                        $this->warn("Team not found in database: {$teamName}");
                    }
                    continue;
                }

                if ($debug) {
                    $this->info("Creating FPI rating: {$teamName} = {$fpiValue}");
                }

                // Create new FPI rating
                FpiRating::create([
                    'team_id' => $team->id,
                    'rating' => $fpiValue,
                    'revision' => $newRevision,
                    'recorded_at' => $timestamp
                ]);

                $count++;
            }

            $this->info("\n✓ Processed {$count} teams");

        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
            if ($debug) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }

        return 0;
    }
}
