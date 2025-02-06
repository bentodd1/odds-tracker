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
    protected $signature = 'fetch:college-basketball-bpi {--debug} {--group=}';
    protected $description = 'Fetch College Basketball BPI ratings from ESPN';
    
    // Add protected property for debug
    protected $debug = false;

    private $teamMappings = [
        // ACC Teams
        'Virginia Cavaliers' => 'Virginia Cavaliers',
        'Miami Hurricanes' => 'Miami (FL) Hurricanes',  // Distinguish from Miami (OH)
        
        // America East Teams
        'Vermont Catamounts' => 'Vermont Catamounts',
        'UAlbany Great Danes' => 'Albany Great Danes',  // UAlbany -> Albany
        
        // Patriot League Teams
        'Colgate Raiders' => 'Colgate Raiders',
        'American University Eagles' => 'American Eagles',
        'Navy Midshipmen' => 'Navy Midshipmen',
        'Lehigh Mountain Hawks' => 'Lehigh Mountain Hawks',
        'Boston University Terriers' => 'Boston Univ. Terriers',
        'Army Black Knights' => 'Army Knights',
        'Loyola Maryland Greyhounds' => 'Loyola (MD) Greyhounds',
        
        // Simple names that need exact mapping
        'Tennessee' => 'Tennessee Volunteers',
        'Alabama' => 'Alabama Crimson Tide',
        'Missouri' => 'Missouri Tigers',
        'Auburn' => 'Auburn Tigers',
        'LSU' => 'LSU Tigers',
        'Memphis' => 'Memphis Tigers',
        'Pacific' => 'Pacific Tigers',
        'Houston' => 'Houston Cougars',
        'BYU' => 'BYU Cougars',
        'Texas Southern' => 'Texas Southern Tigers',
        'Kansas' => 'Kansas Jayhawks',
        'Kansas State' => 'Kansas St Wildcats',
        'Arizona State' => 'Arizona St Sun Devils',
        'Oregon State' => 'Oregon St Beavers',
        'Mississippi State' => 'Mississippi St Bulldogs',
        'Florida State' => 'Florida St Seminoles',
        'Oklahoma State' => 'Oklahoma St Cowboys',
        'Michigan State' => 'Michigan St Spartans',
        'CSU Fullerton' => 'Cal State Fullerton Titans',
        'CSU Bakersfield' => 'CSU Bakersfield Roadrunners',
        'CSU Northridge' => 'CSU Northridge Matadors',
        'San José State' => 'San José St Spartans',
        'Fresno State' => 'Fresno St Bulldogs',
        'Boise State' => 'Boise St Broncos',
        'Mississippi Valley State' => 'Mississippi Valley St Delta Devils',
        'Sacramento State' => 'Sacramento St Hornets',
        'Portland State' => 'Portland St Vikings',
        'Weber State' => 'Weber St Wildcats',
        'Montana State' => 'Montana St Bobcats',
        'Idaho State' => 'Idaho St Bengals',
        'Illinois State' => 'Illinois St Redbirds',
        'Indiana State' => 'Indiana St Sycamores',
        'Ball State' => 'Ball St Cardinals',
        'Kent State' => 'Kent St Golden Flashes',
        
        // Directional Schools
        'South Carolina' => 'South Carolina Gamecocks',
        'North Carolina' => 'North Carolina Tar Heels',
        'South Florida' => 'South Florida Bulls',
        'North Florida' => 'North Florida Ospreys',
        'South Alabama' => 'South Alabama Jaguars',
        'North Alabama' => 'North Alabama Lions',
        'East Carolina' => 'East Carolina Pirates',
        'Western Carolina' => 'Western Carolina Catamounts',
        'Eastern Michigan' => 'Eastern Michigan Eagles',
        'Western Michigan' => 'Western Michigan Broncos',
        'Northern Illinois' => 'Northern Illinois Huskies',
        'Southern Illinois' => 'Southern Illinois Salukis',
        'Southeast Missouri State' => 'Southeast Missouri St Redhawks',
        
        // Special Cases
        'Little Rock Trojans' => 'Arkansas-Little Rock Trojans',
        'Central Connecticut' => 'Central Connecticut St Blue Devils',
        'UL Monroe' => 'UL Monroe Warhawks',
        'Louisiana' => 'Louisiana Ragin\' Cajuns',
        'UNC Wilmington' => 'UNC Wilmington Seahawks',
        'UTEP' => 'Texas-El Paso Miners',
        'UTSA' => 'UTSA Roadrunners',
        'UMass Lowell' => 'UMass-Lowell River Hawks',
        'Saint Joseph\'s' => 'Saint Joseph\'s Hawks',
        'Saint Francis' => 'St. Francis (PA) Red Flash',
        'Mount St. Mary\'s' => 'Mt. St. Mary\'s Mountaineers',
        'Loyola Chicago' => 'Loyola (Chi) Ramblers',
        'Loyola Maryland' => 'Loyola (MD) Greyhounds',
        'Purdue Fort Wayne' => 'Fort Wayne Mastodons',
        'George Washington' => 'GW Revolutionaries',
        
        // Keep existing base mappings
        'Missouri St' => 'Missouri St',
        'Wright St' => 'Wright St',
        'Georgia St' => 'Georgia St',
        'Appalachian St' => 'Appalachian St',
        'Cleveland St' => 'Cleveland St',
        'Boston Univ.' => 'Boston Univ.',
        
        // Conference USA and Similar
        'Florida Atlantic' => 'Florida Atlantic Owls',
        'Florida International' => 'FIU Panthers',
        'Western Kentucky' => 'Western Kentucky Hilltoppers',
        'Middle Tennessee' => 'Middle Tennessee Blue Raiders',
        
        // Military Academies - Updated with full names
        'Army' => 'Army Knights',
        'Army West Point' => 'Army Knights',
        'Air Force' => 'Air Force Falcons',
        'Navy' => 'Navy Midshipmen',
        
        // Common Abbreviations
        'UNLV' => 'UNLV Rebels',
        'UCF' => 'UCF Knights',
        'VCU' => 'VCU Rams',
        'SMU' => 'SMU Mustangs',
        'USC' => 'USC Trojans',
        'UCLA' => 'UCLA Bruins',
        'UAB' => 'UAB Blazers',
        'UIC' => 'UIC Flames',
        'VMI' => 'VMI Keydets',
    ];

    private $suffixesToRemove = [
        ' Cougars',
        ' Panthers',
        ' Bears',
        ' Jaguars',
        ' Raiders',
        ' Mastodons',
        ' Sycamores',
        ' Terriers',
        ' Vikings',
        ' Mountaineers',
        ' Knights',
        ' Blue Devils',
        ' Crimson Tide',
        ' Volunteers',
        ' Tigers',
        ' Cyclones',
        ' Wildcats',
        ' Bulldogs',
        ' Cardinals',
        ' Eagles',
        ' Huskies',
        ' Aggies',
        ' Rams',
        ' Owls',
        ' Colonials',
        ' Pioneers',
        ' Flames',
        ' Dukes',
        ' Gaels',
        ' Broncos',
        ' Spartans',
        ' Hoyas',
        ' Bluejays',
        ' Peacocks',
        ' Red Hawks',
        ' Bobcats',
        ' Seahawks',
        ' Wolf Pack',
        ' Demon Deacons',
        ' Fighting Irish',
        ' Boilermakers',
        ' Cornhuskers',
        ' Razorbacks',
        ' Commodores',
        ' Golden Eagles',
        ' Red Storm',
        ' Fighting Illini',
        ' Hoosiers',
        ' Hawkeyes',
        ' Jayhawks',
        ' Wolverines',
        ' Buckeyes',
        ' Sooners',
        ' Ducks',
        ' Nittany Lions',
        ' Gamecocks',
        ' Longhorns',
        ' Cavaliers',
    ];

    private function normalizeTeamName($name)
    {
        if ($this->debug) {
            $this->info("Normalizing team name: " . $name);
        }

        // Check direct mappings first
        if (isset($this->teamMappings[$name])) {
            if ($this->debug) {
                $this->info("Found direct mapping for: " . $name . " => " . $this->teamMappings[$name]);
            }
            return $this->teamMappings[$name];
        }
        
        // Return original name if no mapping found
        return trim($name);
    }

    public function handle()
    {
        // Set debug flag at the start
        $this->debug = $this->option('debug');
        
        $this->info('Fetching ESPN College Basketball BPI ratings...');
        $group = $this->option('group');

        try {
            $url = 'https://www.espn.com/mens-college-basketball/bpi';
            if ($group) {
                $url .= '/_/group/' . $group;
                $this->info("Fetching group $group from: $url");
            }

            $response = Http::get($url);
            $html = $response->body();

            if ($this->debug) {
                $this->info("\nSaving response to storage/app/debug_ncaab_group_$group.html");
                file_put_contents(storage_path("app/debug_ncaab_group_$group.html"), $html);
            }

            $doc = new DOMDocument();
            @$doc->loadHTML($html);
            $xpath = new DOMXPath($doc);

            // Get all team nodes
            $teamNodes = $xpath->query("//div[contains(@class, 'TeamCell')]//span[contains(@class, 'TeamLink__Name')]//a");
            
            if ($this->debug) {
                $this->info("\nFound " . $teamNodes->length . " teams in response");
                $this->info("\nTeam nodes found:");
                foreach ($teamNodes as $node) {
                    $this->info("Raw team name: " . $node->textContent);
                }
            }

            $teams = [];
            foreach ($teamNodes as $teamNode) {
                $teamName = trim($teamNode->textContent);
                $normalizedName = $this->normalizeTeamName($teamName);
                
                if ($this->debug) {
                    $this->info("\nProcessing team:");
                    $this->info("ESPN Team Name: " . $teamName);
                    $this->info("Normalized Name: " . $normalizedName);
                }

                // Find matching team in database
                $team = Team::whereHas('sport', function($query) {
                    $query->where('title', 'NCAAB');
                })->where(function($query) use ($normalizedName) {
                    $query->where('name', 'LIKE', '%' . $normalizedName . '%')
                        ->orWhere('name', 'LIKE', '%' . str_replace(' ', '%', $normalizedName) . '%');
                })->first();

                if ($this->debug) {
                    if ($team) {
                        $this->info("Found match: " . $team->name);
                    } else {
                        $this->error("No match found in database for: " . $normalizedName);
                        
                        // Show potential close matches
                        $potentialMatches = Team::whereHas('sport', function($query) {
                            $query->where('title', 'NCAAB');
                        })->where('name', 'LIKE', '%' . explode(' ', $normalizedName)[0] . '%')->get();
                        
                        if ($potentialMatches->count() > 0) {
                            $this->info("Potential matches found:");
                            foreach ($potentialMatches as $match) {
                                $this->info("- " . $match->name);
                            }
                        }
                    }
                }

                if ($team) {
                    $teams[] = [
                        'team_id' => $team->id,
                        'espn_name' => $teamName,
                        'normalized_name' => $normalizedName
                    ];
                }
            }

            // ... rest of the code ...

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            if ($this->debug) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }
}
