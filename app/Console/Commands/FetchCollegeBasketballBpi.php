<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\FpiRating;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;

class FetchCollegeBasketballBpi extends Command
{
    protected $signature = 'espn:fetch-college-basketball-bpi {--debug : Show debug information}';
    protected $description = 'Fetch BPI (Basketball Power Index) ratings from ESPN for college basketball teams';

    public function handle()
    {
        $this->info('Fetching ESPN College Basketball BPI ratings...');
        $debug = $this->option('debug');
        $timestamp = now();
        $latestRevision = FpiRating::max('revision') ?? 0;
        $newRevision = $latestRevision + 1;
        $count = 0;

        try {
            // Delete any existing ratings for this revision (in case of partial runs)
            FpiRating::where('revision', $newRevision)->delete();

            // Track processed teams to avoid duplicates
            $processedTeams = [];

            // Loop through groups 1-100
            for ($group = 1; $group <= 100; $group++) {
                if ($debug) {
                    $this->info("\nFetching group {$group}...");
                }

                $response = Http::get("https://www.espn.com/mens-college-basketball/bpi/_/group/{$group}");
                $html = $response->body();

                if ($debug) {
                    file_put_contents(storage_path("app/debug_college_bball_group_{$group}.html"), $html);
                    $this->info("Saved ESPN response to storage/app/debug_college_bball_group_{$group}.html");
                }

                $doc = new DOMDocument();
                @$doc->loadHTML($html);
                $xpath = new DOMXPath($doc);

                // Get teams from the fixed left table
                $teamRows = $xpath->query("//table[contains(@class, 'Table--fixed-left')]//tr[contains(@class, 'Table__TR--sm')]");

                // Get BPI data from the scrollable table
                $dataRows = $xpath->query("//div[contains(@class, 'Table__ScrollerWrapper')]//tr[contains(@class, 'Table__TR--sm')]");

                if ($debug) {
                    $this->info("Found " . $teamRows->length . " team rows and " . $dataRows->length . " data rows in group {$group}");
                }

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
                    // Remove common suffixes for better matching
                    $teamName = str_replace([' Blue Devils', ' Crimson Tide', ' Volunteers', ' Tigers', ' Cyclones', ' Cougars'], '', $teamName);

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

                    // Find team in database - updated to only match NCAAB teams
                    $team = Team::whereHas('sport', function($query) {
                        $query->where('title', 'NCAAB');
                    })
                    ->where(function ($query) use ($teamName) {
                        $query->where('name', $teamName)
                            ->orWhere('name', 'LIKE', "%{$teamName}%");
                    })
                    ->first();

                    if (!$team) {
                        if ($debug) {
                            $this->warn("NCAAB team not found in database: {$teamName}");
                        }
                        continue;
                    }

                    if ($debug) {
                        $this->info("Creating BPI rating: {$teamName} = {$bpiValue}");
                    }

                    // Skip if we've already processed this team
                    if (isset($processedTeams[$team->id])) {
                        if ($debug) {
                            $this->warn("Skipping duplicate team: {$teamName}");
                        }
                        continue;
                    }

                    // Create new FPI/BPI rating
                    FpiRating::create([
                        'team_id' => $team->id,
                        'rating' => $bpiValue,
                        'revision' => $newRevision,
                        'recorded_at' => $timestamp
                    ]);

                    // Mark team as processed
                    $processedTeams[$team->id] = true;
                    $count++;
                }
            }

            $this->info("\n✓ Processed {$count} unique teams total");

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
