<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\FpiRating;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;

class FetchEspnFpi extends Command
{
    protected $signature = 'espn:fetch-fpi {--debug : Show debug information}';
    protected $description = 'Fetch FPI ratings from ESPN';

    public function handle()
    {
        $this->info('Fetching ESPN FPI ratings...');
        $debug = $this->option('debug');

        try {
            $response = Http::get('https://www.espn.com/nfl/fpi');
            $html = $response->body();

            if ($debug) {
                file_put_contents(storage_path('app/debug.html'), $html);
                $this->info("Saved ESPN response to storage/app/debug.html");
            }

            $doc = new DOMDocument();
            @$doc->loadHTML($html);
            $xpath = new DOMXPath($doc);

            // Get rows from each table separately
            $teamRows = $xpath->query("//table[1]//tr[contains(@class, 'Table__TR Table__TR--sm Table__even')]");
            $dataRows = $xpath->query("//div[contains(@class, 'Table__ScrollerWrapper')]//tr[contains(@class, 'Table__TR Table__TR--sm Table__even')]");

            if ($debug) {
                $this->info("Found " . $teamRows->length . " team rows and " . $dataRows->length . " data rows");
            }

            $timestamp = now();
            $count = 0;
            $latestRevision = FpiRating::max('revision') ?? 0;
            $newRevision = $latestRevision + 1;

            foreach ($teamRows as $index => $teamRow) {
                // Get team name
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

                // Need at least 2 cells (W-L-T and FPI)
                if ($cells->length < 2) {
                    if ($debug) {
                        $this->warn("Not enough cells for {$teamName}");
                    }
                    continue;
                }

                // FPI value is in second column
                $fpiValue = trim($cells->item(1)->textContent);
                if (!is_numeric($fpiValue)) {
                    if ($debug) {
                        $this->warn("Invalid FPI value for {$teamName}: {$fpiValue}");
                    }
                    continue;
                }

                // Find team in database
                $team = Team::where('name', $teamName)->first();
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
