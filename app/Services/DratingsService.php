<?php

namespace App\Services;

use GuzzleHttp\Client;
use DOMDocument;
use DOMXPath;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Game;
use App\Models\DratingsPrediction;

class DratingsService
{
    protected $baseUrl = 'https://www.dratings.com/predictor/mlb-baseball-predictions/';
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);
    }

    public function getMlbPredictions()
    {
        try {
            $response = $this->client->get($this->baseUrl);
            $html = $response->getBody()->getContents();
            
            $doc = new DOMDocument();
            @$doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($doc);
            
            $games = [];
            $gameRows = $xpath->query("//tr[.//td[contains(@class, 'table-division')]]");
            
            Log::info('Found ' . $gameRows->length . ' games to process');
            
            foreach ($gameRows as $index => $row) {
                try {
                    Log::debug("Processing row " . ($index + 1));

                    // Get game time
                    $timeNode = $xpath->query(".//time[@datetime]", $row)->item(0);
                    if (!$timeNode) {
                        Log::warning('Could not find game time, skipping row');
                        continue;
                    }
                    $gameTime = Carbon::parse($timeNode->getAttribute('datetime'));

                    // Get teams
                    $teamNodes = $xpath->query(".//td[contains(@class, 'tf--body')]//a[contains(@href, '/teams/')]", $row);
                    if ($teamNodes->length < 2) {
                        $teamSpans = $xpath->query(".//td[contains(@class, 'tf--body')]//span[contains(@class, 'd--ib')]", $row);
                        if ($teamSpans->length < 2) {
                            Log::warning('Could not find both teams, skipping row');
                            continue;
                        }
                        $awayTeam = trim(preg_replace('/\s*\([^)]*\)/', '', $teamSpans->item(0)->textContent));
                        $homeTeam = trim(preg_replace('/\s*\([^)]*\)/', '', $teamSpans->item(1)->textContent));
                    } else {
                        $awayTeam = trim($teamNodes->item(0)->textContent);
                        $homeTeam = trim($teamNodes->item(1)->textContent);
                    }

                    // Get probabilities
                    $probNodes = $xpath->query(".//td[@class='table-division']/span[contains(@class, 'tc--')]", $row);
                    if ($probNodes->length < 2) {
                        Log::warning('Could not find probability nodes, skipping row');
                        continue;
                    }
                    
                    $awayProb = (float) trim(str_replace(['%', ','], '', $probNodes->item(0)->textContent));
                    $homeProb = (float) trim(str_replace(['%', ','], '', $probNodes->item(1)->textContent));

                    // Get Vegas odds
                    $vegasOddsDiv = $xpath->query(".//td/div[@class='vegas-sportsbook']", $row)->item(0);
                    if (!$vegasOddsDiv) {
                        Log::warning('Could not find vegas-sportsbook div, skipping row');
                        continue;
                    }

                    $oddsText = $vegasOddsDiv->textContent;
                    if (preg_match('/([+-]\d+)([+-]\d+)/', $oddsText, $matches)) {
                        $awayOdds = (int) $matches[1];
                        $homeOdds = (int) $matches[2];
                        
                        Log::debug("Parsed odds: Away {$awayOdds}, Home {$homeOdds}");
                        
                        if ($awayOdds === 0 || $homeOdds === 0) {
                            Log::warning('Invalid odds values found, skipping row');
                            continue;
                        }

                        // Create game data as arrays instead of objects
                        $games[] = [
                            'game' => [
                                'awayTeam' => $awayTeam,
                                'homeTeam' => $homeTeam,
                                'startTime' => $gameTime->toDateTimeString()
                            ],
                            'prediction' => [
                                'awayProbability' => $awayProb,
                                'homeProbability' => $homeProb,
                                'awayOdds' => $awayOdds,
                                'homeOdds' => $homeOdds,
                                'source' => 'dratings',
                                'createdAt' => now()->toDateTimeString()
                            ]
                        ];
                    } else {
                        Log::warning('Could not parse odds values from text: ' . $oddsText);
                        continue;
                    }

                } catch (\Exception $e) {
                    Log::error("Error processing row: " . $e->getMessage());
                    continue;
                }
            }

            Log::info('Successfully processed ' . count($games) . ' games');
            return $games;

        } catch (\Exception $e) {
            Log::error("Error fetching predictions: " . $e->getMessage());
            return [];
        }
    }
}