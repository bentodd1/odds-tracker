<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Game;
use App\Models\Score;
use App\Models\Sport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;
use App\Models\Team;

class MatchNCAABScores extends Command
{
    protected $signature = 'match:ncaab-scores
        {--date= : The date to fetch scores for (YYYY-MM-DD format)}
        {--debug : Show debug information}';
    protected $description = 'Match NCAA basketball scores from NCAA.com to existing games';

    private $unmatched = [];
    private $matched = [];
    private $sport;

    // Common team name variations to help with matching
    private $teamMappings = [
        // Common abbreviations
        'usc' => ['southern california', 'southern cal'],
        'ucf' => ['central florida', 'cent florida'],
        'uconn' => ['connecticut'],
        'umass' => ['massachusetts'],
        'smu' => ['southern methodist'],
        'tcu' => ['texas christian'],
        'ucla' => ['california los angeles'],
        'unlv' => ['nevada las vegas'],
        'utep' => ['texas el paso'],
        'unc' => ['north carolina'],
        'ole miss' => ['mississippi'],
        'nc state' => ['north carolina state'],

        // Saint/St. variations
        'saint johns' => ['st johns', 'st. johns', 'st. john\'s', 'st johns red storm'],
        'saint marys' => ['st marys', 'st. marys', 'saint marys gaels'],
        'saint louis' => ['st louis', 'st. louis', 'saint louis billikens'],
        'saint josephs' => ['st josephs', 'st. josephs', 'saint josephs hawks'],
        'saint peters' => ['st peters', 'st. peters'],
        'saint bonaventure' => ['st bonaventure', 'st. bonaventure'],
        'saint francis' => ['st francis', 'st. francis'],
        'saint thomas' => ['st thomas', 'st. thomas'],

        // Common suffixes to remove
        'blue devils' => ['duke'],
        'crimson tide' => ['alabama'],
        'volunteers' => ['tennessee'],
        'tigers' => ['memphis', 'missouri', 'auburn', 'clemson', 'lsu'],
        'wildcats' => ['kentucky', 'arizona', 'kansas state', 'villanova'],
        'bulldogs' => ['gonzaga', 'butler', 'georgia', 'mississippi state'],
        'cardinals' => ['louisville', 'stanford'],
        'bears' => ['baylor', 'california'],
        'hoyas' => ['georgetown'],
        'jayhawks' => ['kansas'],
        'tar heels' => ['north carolina'],
        'wolfpack' => ['nc state'],
        'orange' => ['syracuse'],
        'spartans' => ['michigan state'],
        'wolverines' => ['michigan'],
        'buckeyes' => ['ohio state'],
        'boilermakers' => ['purdue'],
        'hoosiers' => ['indiana'],
        'hawkeyes' => ['iowa'],
        'cyclones' => ['iowa state'],
        'razorbacks' => ['arkansas'],
        'gators' => ['florida'],
        'seminoles' => ['florida state'],
        'hurricanes' => ['miami fl', 'miami florida'],
        'cavaliers' => ['virginia'],
        'hokies' => ['virginia tech'],

        // State variations
        'mississippi state' => ['miss state', 'miss st'],
        'michigan state' => ['mich state', 'mich st'],
        'florida state' => ['fla state', 'fla st'],
        'kansas state' => ['kan state', 'kan st', 'k-state'],
        'arizona state' => ['asu', 'ariz state', 'ariz st'],
        'arkansas state' => ['ark state', 'ark st'],
        'louisiana state' => ['lsu', 'la state'],
        'ohio state' => ['osu'],
        'oklahoma state' => ['okla state', 'okla st', 'ok state'],
        'oregon state' => ['ore state', 'ore st'],

        // Directional schools
        'north carolina' => ['unc', 'n carolina', 'n.c.'],
        'south carolina' => ['s carolina', 's.c.'],
        'west virginia' => ['w virginia', 'w.v.'],
        'east carolina' => ['e carolina', 'e.c.'],
        'northern illinois' => ['n illinois', 'n. illinois'],
        'southern illinois' => ['s illinois', 's. illinois'],
        'western kentucky' => ['w kentucky', 'w. kentucky'],
        'eastern kentucky' => ['e kentucky', 'e. kentucky'],

        // Other common variations
        'miami fl' => ['miami florida', 'miami (fl)', 'miami (fla)'],
        'miami oh' => ['miami ohio', 'miami (oh)', 'miami (ohio)'],
        'texas am' => ['texas a&m', 'texas a & m'],
        'bowling green' => ['bowling green state', 'bgsu'],
        'brigham young' => ['byu'],
        'central michigan' => ['cent michigan', 'cmu'],
        'cincinnati' => ['cincy'],
        'colorado state' => ['colo state', 'colo st'],
        'depaul' => ['de paul'],
        'detroit mercy' => ['detroit'],
        'illinois chicago' => ['uic'],
        'loyola chicago' => ['loyola il', 'loyola (il)'],
        'loyola marymount' => ['lmu'],
        'massachusetts lowell' => ['umass lowell'],
        'middle tennessee' => ['middle tenn', 'mtsu'],
        'mississippi valley state' => ['mvsu'],
        'missouri kansas city' => ['umkc'],
        'montana state' => ['mont state', 'mont st'],
        'nevada las vegas' => ['unlv'],
        'new mexico state' => ['nm state', 'nmsu'],
        'north carolina at' => ['nc at', 'north carolina a&t'],
        'north carolina central' => ['nc central'],
        'north dakota state' => ['ndsu'],
        'south dakota state' => ['sdsu'],
    ];

    public function handle()
    {
        $this->sport = Sport::where('key', 'basketball_ncaab')->first();
        if (!$this->sport) {
            $this->error('Sport not found');
            return 1;
        }

        // Get target date from option or use today
        $targetDate = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', $this->option('date'))
            : Carbon::today();

        // First, fetch all NCAA games
        $ncaaGames = $this->fetchNcaaGames($targetDate);
        $this->info("Found " . count($ncaaGames) . " NCAA games for {$targetDate->format('Y-m-d')}");

        $newScores = 0;
        $updatedScores = 0;

        foreach ($ncaaGames as $ncaaGame) {
            // Record the score
            $result = $this->recordGameScore($ncaaGame['game'], [
                'date' => $targetDate->format('Y/m/d'),
                'home_score' => $ncaaGame['home_score'],
                'away_score' => $ncaaGame['away_score']
            ]);

            if ($result === 'created') {
                $newScores++;
            } elseif ($result === 'updated') {
                $updatedScores++;
            }
        }

        $this->info("\nScores processed:");
        $this->info("New scores: {$newScores}");
        $this->info("Updated scores: {$updatedScores}");

        return 0;
    }

    private function fetchNcaaGames($targetDate)
    {
        $url = "https://www.ncaa.com/scoreboard/basketball-men/d1/{$targetDate->format('Y/m/d')}/all-conf";
        $response = Http::get($url);

        if (!$response->successful()) {
            $this->error("Failed to fetch scores from NCAA.com");
            return [];
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($response->body());
        $xpath = new DOMXPath($doc);

        $games = [];
        $gameContainers = $xpath->query("//div[contains(@class, 'gamePod-type-game')]");

        foreach ($gameContainers as $container) {
            $teams = $xpath->query(".//ul[contains(@class, 'gamePod-game-teams')]/li", $container);

            if ($teams->length !== 2) {
                continue;
            }

            $homeTeamName = trim($xpath->query(".//span[contains(@class, 'gamePod-game-team-name')][not(contains(@class, 'short'))]", $teams->item(0))->item(0)->textContent);
            $awayTeamName = trim($xpath->query(".//span[contains(@class, 'gamePod-game-team-name')][not(contains(@class, 'short'))]", $teams->item(1))->item(0)->textContent);

            $homeScore = (int)trim($xpath->query(".//span[contains(@class, 'gamePod-game-team-score')]", $teams->item(0))->item(0)->textContent);
            $awayScore = (int)trim($xpath->query(".//span[contains(@class, 'gamePod-game-team-score')]", $teams->item(1))->item(0)->textContent);

            if (!$homeTeamName || !$awayTeamName || !$homeScore || !$awayScore) {
                continue;
            }

            $this->info("\nFound NCAA game: {$homeTeamName} ({$homeScore}) vs {$awayTeamName} ({$awayScore})");

            // Find matching teams
            $homeTeam = $this->findMatchingTeam($homeTeamName);
            $awayTeam = $this->findMatchingTeam($awayTeamName);

            if (!$homeTeam || !$awayTeam) {
                $this->error("Could not find matching teams in database");
                $this->info("Home team found: " . ($homeTeam ? "Yes" : "No"));
                $this->info("Away team found: " . ($awayTeam ? "Yes" : "No"));
                continue;
            }

            $this->info("Matched to: {$homeTeam->name} vs {$awayTeam->name}");

            // Search for game with expanded date range
            $startDate = $targetDate->copy()->startOfDay()->subHours(14);
            $endDate = $targetDate->copy()->endOfDay()->addHours(14);

            $this->info("Searching for game between {$startDate} and {$endDate}");

            $query = Game::where('sport_id', $this->sport->id)
                ->where(function($query) use ($homeTeam, $awayTeam) {
                    $query->where(function($q) use ($homeTeam, $awayTeam) {
                        $q->where('home_team_id', $homeTeam->id)
                          ->where('away_team_id', $awayTeam->id);
                    })->orWhere(function($q) use ($homeTeam, $awayTeam) {
                        $q->where('home_team_id', $awayTeam->id)
                          ->where('away_team_id', $homeTeam->id);
                    });
                })
                ->whereBetween('commence_time', [$startDate, $endDate]);

            $this->info("SQL Query: " . $query->toSql());
            $this->info("Bindings: " . json_encode($query->getBindings()));

            $game = $query->first();

            if (!$game) {
                // Check if any game exists between these teams regardless of date
                $anyGame = Game::where('sport_id', $this->sport->id)
                    ->where(function($query) use ($homeTeam, $awayTeam) {
                        $query->where(function($q) use ($homeTeam, $awayTeam) {
                            $q->where('home_team_id', $homeTeam->id)
                              ->where('away_team_id', $awayTeam->id);
                        })->orWhere(function($q) use ($homeTeam, $awayTeam) {
                            $q->where('home_team_id', $awayTeam->id)
                              ->where('away_team_id', $homeTeam->id);
                        });
                    })
                    ->first();

                if ($anyGame) {
                    $this->info("Found game but outside date range. Game time: " . $anyGame->commence_time);
                } else {
                    $this->error("No game found at all between these teams");
                }
                continue;
            }

            $this->info("Found matching game in database (ID: {$game->id})");

            $games[] = [
                'game' => $game,
                'date' => $targetDate->format('Y/m/d'),
                'home_score' => $homeScore,
                'away_score' => $awayScore
            ];
        }

        return $games;
    }

    private function getTeamMappings()
    {
        return [
            'Abilene Christian' => 'Abilene Christian Wildcats',
            'Air Force' => 'Air Force Falcons',
            'Akron' => 'Akron Zips',
            'Alabama' => 'Alabama A&M Bulldogs',
            'Alabama A&M' => 'Alabama A&M Bulldogs',
            'Alabama St.' => 'Alabama St Hornets',
            'Alcorn' => 'Alcorn St Braves',
            'American' => 'American Eagles',
            'Arizona' => 'Arizona St Sun Devils',
            'Arizona St.' => 'Arizona St Sun Devils',
            'Arkansas' => 'Arkansas Razorbacks',
            'Arkansas St.' => 'Arkansas St Red Wolves',
            'Auburn' => 'Auburn Tigers',
            'Austin Peay' => 'Austin Peay Governors',
            'BYU' => 'BYU Cougars',
            'Ball St.' => 'Ball State Cardinals',
            'Baylor' => 'Baylor Bears',
            'Bellarmine' => 'Bellarmine Knights',
            'Belmont' => 'Belmont Bruins',
            'Bethune-Cookman' => 'Bethune-Cookman Wildcats',
            'Binghamton' => 'Binghamton Bearcats',
            'Boise St.' => 'Boise State Broncos',
            'Boston College' => 'Boston College Eagles',
            'Boston U.' => 'Boston Univ. Terriers',
            'Bowling Green' => 'Bowling Green Falcons',
            'Bradley' => 'Bradley Braves',
            'Brown' => 'Brown Bears',
            'Bryant' => 'Bryant Bulldogs',
            'Bucknell' => 'Bucknell Bison',
            'Buffalo' => 'Buffalo Bulls',
            'Butler' => 'Butler Bulldogs',
            'CSU Bakersfield' => 'CSU Bakersfield Roadrunners',
            'Cal Poly' => 'Cal Poly Mustangs',
            'California' => 'California Golden Bears',
            'Campbell' => 'Campbell Fighting Camels',
            'Canisius' => 'Canisius Golden Griffins',
            'Central Ark.' => 'Central Arkansas Bears',
            'Central Mich.' => 'Central Michigan Chippewas',
            'Charleston So.' => 'Charleston Southern Buccaneers',
            'Charlotte' => 'Charlotte 49ers',
            'Chattanooga' => 'Chattanooga Mocs',
            'Chicago St.' => 'Chicago St Cougars',
            'Cincinnati' => 'Cincinnati Bearcats',
            'Clemson' => 'Clemson Tigers',
            'Cleveland St.' => 'Cleveland St Vikings',
            'Coastal Carolina' => 'Coastal Carolina Chanticleers',
            'Colorado' => 'Colorado Buffaloes',
            'Colorado St.' => 'Colorado St Rams',
            'Columbia' => 'Columbia Lions',
            'Coppin St.' => 'Coppin St Eagles',
            'Cornell' => 'Cornell Big Red',
            'Creighton' => 'Creighton Bluejays',
            'Dartmouth' => 'Dartmouth Big Green',
            'Davidson' => 'Davidson Wildcats',
            'Dayton' => 'Dayton Flyers',
            'DePaul' => 'DePaul Blue Demons',
            'Delaware' => 'Delaware Blue Hens',
            'Delaware St.' => 'Delaware St Hornets',
            'Denver' => 'Denver Pioneers',
            'Detroit Mercy' => 'Detroit Mercy Titans',
            'Drake' => 'Drake Bulldogs',
            'Drexel' => 'Drexel Dragons',
            'Duke' => 'Duke Blue Devils',
            'Duquesne' => 'Duquesne Dukes',
            'East Carolina' => 'East Carolina Pirates',
            'Eastern Ill.' => 'Eastern Illinois Panthers',
            'Eastern Mich.' => 'Eastern Michigan Eagles',
            'Eastern Wash.' => 'Eastern Washington Eagles',
            'Elon' => 'Elon Phoenix',
            'Evansville' => 'Evansville Purple Aces',
            'Fairfield' => 'Fairfield Stags',
            'Florida' => 'Florida A&M Rattlers',
            'Florida A&M' => 'Florida A&M Rattlers',
            'Florida St.' => 'Florida St Seminoles',
            'Fordham' => 'Fordham Rams',
            'Fresno St.' => 'Fresno St Bulldogs',
            'Furman' => 'Furman Paladins',
            'Gardner-Webb' => 'Gardner-Webb Bulldogs',
            'George Mason' => 'George Mason Patriots',
            'Georgetown' => 'Georgetown Hoyas',
            'Georgia' => 'Georgia Bulldogs',
            'Georgia St.' => 'Georgia St Panthers',
            'Georgia Tech' => 'Georgia Tech Yellow Jackets',
            'Gonzaga' => 'Gonzaga Bulldogs',
            'Grambling' => 'Grambling St Tigers',
            'Grand Canyon' => 'Grand Canyon Antelopes',
            'Green Bay' => 'Green Bay Phoenix',
            'Hampton' => 'Hampton Pirates',
            'Harvard' => 'Harvard Crimson',
            'Hawaii' => 'Hawai\'i Rainbow Warriors',
            'High Point' => 'High Point Panthers',
            'Hofstra' => 'Hofstra Pride',
            'Holy Cross' => 'Holy Cross Crusaders',
            'Houston' => 'Houston Christian Huskies',
            'Houston Christian' => 'Houston Christian Huskies',
            'Howard' => 'Howard Bison',
            'IUPUI' => 'IUPUI Jaguars',
            'Idaho' => 'Idaho State Bengals',
            'Idaho St.' => 'Idaho State Bengals',
            'Illinois' => 'Eastern Illinois Panthers',
            'Illinois St.' => 'Illinois St Redbirds',
            'Indiana' => 'Indiana Hoosiers',
            'Indiana St.' => 'Indiana St Sycamores',
            'Iona' => 'GW Revolutionaries',
            'Iowa' => 'Iowa Hawkeyes',
            'Iowa St.' => 'Iowa State Cyclones',
            'Jackson St.' => 'Jackson St Tigers',
            'Jacksonville' => 'Jacksonville Dolphins',
            'Jacksonville St.' => 'Jacksonville St Gamecocks',
            'James Madison' => 'James Madison Dukes',
            'Kansas' => 'Arkansas Razorbacks',
            'Kansas St.' => 'Arkansas St Red Wolves',
            'Kennesaw St.' => 'Kennesaw St Owls',
            'Kent St.' => 'Kent State Golden Flashes',
            'Kentucky' => 'Eastern Kentucky Colonels',
            'LIU' => 'LIU Sharks',
            'LSU' => 'LSU Tigers',
            'La Salle' => 'La Salle Explorers',
            'Lafayette' => 'Lafayette Leopards',
            'Le Moyne' => 'Le Moyne Dolphins',
            'Liberty' => 'Liberty Flames',
            'Lindenwood' => 'Lindenwood Lions',
            'Lipscomb' => 'Lipscomb Bisons',
            'Little Rock' => 'Arkansas-Little Rock Trojans',
            'Long Beach St.' => 'Long Beach St 49ers',
            'Longwood' => 'Longwood Lancers',
            'Louisiana' => 'Louisiana Ragin\' Cajuns',
            'Louisiana Tech' => 'Louisiana Tech Bulldogs',
            'Louisville' => 'Louisville Cardinals',
            'Maine' => 'Maine Black Bears',
            'Manhattan' => 'Manhattan Jaspers',
            'Marist' => 'Marist Red Foxes',
            'Marquette' => 'Marquette Golden Eagles',
            'Marshall' => 'Marshall Thundering Herd',
            'Maryland' => 'Maryland Terrapins',
            'Massachusetts' => 'Massachusetts Minutemen',
            'McNeese' => 'McNeese Cowboys',
            'Memphis' => 'Memphis Tigers',
            'Mercer' => 'Mercer Bears',
            'Merrimack' => 'Merrimack Warriors',
            'Miami (OH)' => 'Miami (OH) RedHawks',
            'Michigan' => 'Central Michigan Chippewas',
            'Michigan St.' => 'Michigan St Spartans',
            'Middle Tenn.' => 'Middle Tennessee Blue Raiders',
            'Milwaukee' => 'Milwaukee Panthers',
            'Minnesota' => 'Minnesota Golden Gophers',
            'Mississippi St.' => 'Mississippi St Bulldogs',
            'Missouri' => 'Missouri St Bears',
            'Missouri St.' => 'Missouri St Bears',
            'Monmouth' => 'Monmouth Hawks',
            'Montana' => 'Montana Grizzlies',
            'Montana St.' => 'Montana St Bobcats',
            'Morehead St.' => 'Morehead St Eagles',
            'Morgan St.' => 'Morgan St Bears',
            'Murray St.' => 'Murray St Racers',
            'NC State' => 'NC State Wolfpack',
            'NJIT' => 'NJIT Highlanders',
            'Navy' => 'Navy Midshipmen',
            'Nebraska' => 'Nebraska Cornhuskers',
            'Nevada' => 'Nevada Wolf Pack',
            'New Hampshire' => 'New Hampshire Wildcats',
            'New Mexico' => 'New Mexico Lobos',
            'New Mexico St.' => 'New Mexico St Aggies',
            'New Orleans' => 'New Orleans Privateers',
            'Niagara' => 'Niagara Purple Eagles',
            'Nicholls' => 'Nicholls St Colonels',
            'Norfolk St.' => 'Norfolk St Spartans',
            'North Ala.' => 'North Alabama Lions',
            'North Carolina' => 'North Carolina A&T Aggies',
            'North Dakota' => 'North Dakota Fighting Hawks',
            'North Dakota St.' => 'North Dakota St Bison',
            'North Florida' => 'North Florida Ospreys',
            'Northeastern' => 'Northeastern Huskies',
            'Northern Ariz.' => 'Northern Arizona Lumberjacks',
            'Northwestern' => 'Northwestern St Demons',
            'Northwestern St.' => 'Northwestern St Demons',
            'Notre Dame' => 'Notre Dame Fighting Irish',
            'Oakland' => 'Oakland Golden Grizzlies',
            'Ohio' => 'Ohio Bobcats',
            'Ohio St.' => 'Ohio State Buckeyes',
            'Oklahoma' => 'Oklahoma Sooners',
            'Oklahoma St.' => 'Oklahoma St Cowboys',
            'Old Dominion' => 'Old Dominion Monarchs',
            'Ole Miss' => 'Ole Miss Rebels',
            'Omaha' => 'Omaha Mavericks',
            'Oregon' => 'Oregon Ducks',
            'Oregon St.' => 'Oregon St Beavers',
            'Pacific' => 'Pacific Tigers',
            'Penn' => 'Penn State Nittany Lions',
            'Penn St.' => 'Penn State Nittany Lions',
            'Pepperdine' => 'Pepperdine Waves',
            'Portland' => 'Portland Pilots',
            'Portland St.' => 'Portland St Vikings',
            'Prairie View' => 'Prairie View Panthers',
            'Presbyterian' => 'Presbyterian Blue Hose',
            'Princeton' => 'Princeton Tigers',
            'Providence' => 'Providence Friars',
            'Purdue' => 'Purdue Boilermakers',
            'Quinnipiac' => 'Quinnipiac Bobcats',
            'Radford' => 'Radford Highlanders',
            'Rhode Island' => 'Rhode Island Rams',
            'Rice' => 'Rice Owls',
            'Richmond' => 'Richmond Spiders',
            'Rider' => 'Rider Broncs',
            'Robert Morris' => 'Robert Morris Colonials',
            'Rutgers' => 'Rutgers Scarlet Knights',
            'SIUE' => 'SIU-Edwardsville Cougars',
            'SMU' => 'SMU Mustangs',
            'Sacramento St.' => 'Sacramento St Hornets',
            'Sacred Heart' => 'Sacred Heart Pioneers',
            'Saint Louis' => 'Saint Louis Billikens',
            'Saint Peter\'s' => 'Saint Peter\'s Peacocks',
            'Sam Houston' => 'Sam Houston St Bearkats',
            'Samford' => 'Samford Bulldogs',
            'San Diego' => 'San Diego St Aztecs',
            'San Diego St.' => 'San Diego St Aztecs',
            'San Francisco' => 'San Francisco Dons',
            'Santa Clara' => 'Santa Clara Broncos',
            'Seton Hall' => 'Seton Hall Pirates',
            'Siena' => 'Siena Saints',
            'South Alabama' => 'South Alabama Jaguars',
            'South Carolina' => 'South Carolina Gamecocks',
            'South Carolina St.' => 'South Carolina St Bulldogs',
            'South Dakota' => 'South Dakota Coyotes',
            'South Dakota St.' => 'South Dakota St Jackrabbits',
            'Southern Ill.' => 'Southern Illinois Salukis',
            'Southern Ind.' => 'Southern Indiana Screaming Eagles',
            'Southern Miss.' => 'Southern Miss Golden Eagles',
            'Southern U.' => 'Southern Utah Thunderbirds',
            'Southern Utah' => 'Southern Utah Thunderbirds',
            'St. Bonaventure' => 'St. Bonaventure Bonnies',
            'St. Thomas (MN)' => 'St. Thomas (MN) Tommies',
            'Stanford' => 'Stanford Cardinal',
            'Stetson' => 'Stetson Hatters',
            'Stonehill' => 'Stonehill Skyhawks',
            'Stony Brook' => 'Stony Brook Seawolves',
            'Syracuse' => 'Syracuse Orange',
            'TCU' => 'TCU Horned Frogs',
            'Tarleton St.' => 'Tarleton State Texans',
            'Temple' => 'Temple Owls',
            'Tennessee' => 'East Tennessee St Buccaneers',
            'Tennessee St.' => 'East Tennessee St Buccaneers',
            'Tennessee Tech' => 'Tennessee Tech Golden Eagles',
            'Texas' => 'Texas A&M-CC Islanders',
            'Texas A&M' => 'Texas A&M-CC Islanders',
            'Texas Southern' => 'Texas Southern Tigers',
            'Texas St.' => 'Texas State Bobcats',
            'Texas Tech' => 'Texas Tech Red Raiders',
            'The Citadel' => 'The Citadel Bulldogs',
            'Toledo' => 'Toledo Rockets',
            'Towson' => 'Towson Tigers',
            'Troy' => 'Troy Trojans',
            'Tulane' => 'Tulane Green Wave',
            'Tulsa' => 'Tulsa Golden Hurricane',
            'UAB' => 'UAB Blazers',
            'UC Davis' => 'UC Davis Aggies',
            'UC Irvine' => 'UC Irvine Anteaters',
            'UC Riverside' => 'UC Riverside Highlanders',
            'UC San Diego' => 'UC San Diego Tritons',
            'UC Santa Barbara' => 'UC Santa Barbara Gauchos',
            'UCF' => 'UCF Knights',
            'UCLA' => 'UCLA Bruins',
            'UConn' => 'UConn Huskies',
            'UIC' => 'UIC Flames',
            'UMBC' => 'UMBC Retrievers',
            'UMass Lowell' => 'UMass Lowell River Hawks',
            'UNC Asheville' => 'UNC Asheville Bulldogs',
            'UNC Greensboro' => 'UNC Greensboro Spartans',
            'UNI' => 'Boston Univ. Terriers',
            'UNLV' => 'UNLV Rebels',
            'UTEP' => 'UTEP Miners',
            'UTSA' => 'UTSA Roadrunners',
            'Utah' => 'Southern Utah Thunderbirds',
            'Utah St.' => 'Utah State Aggies',
            'Utah Tech' => 'Utah Tech Trailblazers',
            'Utah Valley' => 'Utah Valley Wolverines',
            'VCU' => 'VCU Rams',
            'VMI' => 'VMI Keydets',
            'Valparaiso' => 'Valparaiso Beacons',
            'Vanderbilt' => 'Vanderbilt Commodores',
            'Villanova' => 'Villanova Wildcats',
            'Virginia' => 'Virginia Tech Hokies',
            'Virginia Tech' => 'Virginia Tech Hokies',
            'Wagner' => 'Wagner Seahawks',
            'Wake Forest' => 'Wake Forest Demon Deacons',
            'Washington' => 'Eastern Washington Eagles',
            'Washington St.' => 'Washington St Cougars',
            'Weber St.' => 'Weber State Wildcats',
            'West Virginia' => 'West Virginia Mountaineers',
            'Western Caro.' => 'Western Carolina Catamounts',
            'Western Ill.' => 'Western Illinois Leathernecks',
            'Western Mich.' => 'Western Michigan Broncos',
            'Wichita St.' => 'Wichita St Shockers',
            'William & Mary' => 'William & Mary Tribe',
            'Winthrop' => 'Winthrop Eagles',
            'Wisconsin' => 'Wisconsin Badgers',
            'Wofford' => 'Wofford Terriers',
            'Wright St.' => 'Wright St Raiders',
            'Wyoming' => 'Wyoming Cowboys',
            'Xavier' => 'Xavier Musketeers',
            'Yale' => 'Yale Bulldogs',
            'Youngstown St.' => 'Youngstown St Penguins'
        ];
    }

    private function findMatchingTeam($ncaaTeamName)
    {
        $mappings = $this->getTeamMappings();

        // If we have a direct mapping, use it
        $dbTeamName = $mappings[$ncaaTeamName] ?? null;

        if ($dbTeamName) {
            return Team::where('name', $dbTeamName)
                      ->where('sport_id', $this->sport->id)
                      ->first();
        }

        return null;
    }

    private function recordGameScore($game, $score)
    {
        try {
            $this->log("\nRecording score for game:");
            $this->log("Game ID: " . $game->id);
            $this->log("Teams: " . $game->homeTeam->name . " vs " . $game->awayTeam->name);
            
            $homeFpi = $game->homeTeam->latestFpi()->first();
            $awayFpi = $game->awayTeam->latestFpi()->first();

            $this->log("Home FPI: " . ($homeFpi ? $homeFpi->rating : 'null'));
            $this->log("Away FPI: " . ($awayFpi ? $awayFpi->rating : 'null'));

            $scoreData = [
                'game_id' => $game->id,
                'period' => 'F',  // Final score
                'home_score' => $score['home_score'],
                'away_score' => $score['away_score'],
                'home_fpi' => $homeFpi ? $homeFpi->rating : null,
                'away_fpi' => $awayFpi ? $awayFpi->rating : null,
                'date' => Carbon::createFromFormat('Y/m/d', $score['date'])->startOfDay(),
            ];

            $this->log("Score data to be saved:");
            $this->log(json_encode($scoreData, JSON_PRETTY_PRINT));

            $exists = Score::where([
                'game_id' => $game->id,
                'period' => 'F'
            ])->exists();

            $result = Score::updateOrCreate(
                [
                    'game_id' => $game->id,
                    'period' => 'F'
                ],
                $scoreData
            );

            $this->log("Score saved successfully: " . $result->id);
            
            return $exists ? 'updated' : 'created';

        } catch (\Exception $e) {
            $this->log("Error recording score: " . $e->getMessage(), 'error');
            $this->log("Stack trace: " . $e->getTraceAsString(), 'error');
            return null;
        }
    }

    private function log($message, $type = 'info')
    {
        // Console output
        if ($this->option('debug')) {
            if ($type === 'error') {
                $this->error($message);
            } else {
                $this->info($message);
            }
        }

        // File logging - ensure the logs directory exists
        $date = now()->format('Y-m-d');
        $logPath = storage_path("logs/ncaab-scores-{$date}.log");

        // Create logs directory if it doesn't exist
        if (!File::exists(dirname($logPath))) {
            File::makeDirectory(dirname($logPath), 0755, true);
        }

        $timestamp = now()->format('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$type}: {$message}\n";

        File::append($logPath, $logMessage);
    }
}
