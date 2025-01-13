<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\FpiRating;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;

class FetchNBABpi extends Command
{
    protected $signature = 'espn:fetch-nba-bpi {--debug : Show debug information}';
    protected $description = 'Fetch BPI (Basketball Power Index) ratings from ESPN for NBA teams';

    public function handle()
    {
        $this->info('Fetching ESPN NBA BPI ratings...');
        $debug = $this->option('debug');

        try {
            $response = Http::get('https://www.espn.com/nba/bpi');
            $html = $response->body();

            if ($debug) {
                file_put_contents(storage_path('app/debug_nba.html'), $html);
                $this->info("Saved ESPN response to storage/app/debug_nba.html");
            }

            $doc = new DOMDocument();
            @$doc->loadHTML($html);
            $xpath = new DOMXPath($doc);

            // Get teams from the fixed left table
            $teamRows = $xpath->query("//table[contains(@class, 'Table--fixed-left')]//tr[contains(@class, 'Table__TR--sm')]");

            // Get BPI data from the scrollable table
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
                // Store original team name before modifications
                $originalTeamName = $teamName;
                
                // More specific team name mapping
                $teamMappings = [
                    'LA Lakers' => 'Los Angeles Lakers',
                    'LA Clippers' => 'Los Angeles Clippers',
                    'NY Knicks' => 'New York Knicks',
                    'GS Warriors' => 'Golden State Warriors',
                ];
                
                $teamName = $teamMappings[$teamName] ?? $teamName;

                // Find team in database with more precise matching
                $team = Team::where(function ($query) use ($teamName, $originalTeamName) {
                    $query->where('name', $teamName)
                          ->orWhere('name', $originalTeamName);
                })->first();

                if (!$team) {
                    if ($debug) {
                        $this->warn("Team not found in database: {$teamName} (Original: {$originalTeamName})");
                    }
                    continue;
                }

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

                // BPI is in the second cell (index 1) for basketball
                $bpiCell = $cells->item(1);
                if (!$bpiCell) {
                    if ($debug) {
                        $this->warn("No BPI cell found for {$teamName}");
                    }
                    continue;
                }

                $bpiValue = trim($bpiCell->textContent);
                if (!is_numeric($bpiValue)) {
                    if ($debug) {
                        $this->warn("Invalid BPI value for {$teamName}: {$bpiValue}");
                    }
                    continue;
                }

                if ($debug) {
                    $this->info("Creating BPI rating: {$teamName} = {$bpiValue}");
                }

                // Create new FPI/BPI rating
                FpiRating::create([
                    'team_id' => $team->id,
                    'rating' => $bpiValue,
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