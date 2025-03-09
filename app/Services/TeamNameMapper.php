<?php

namespace App\Services;

class TeamNameMapper
{
    protected array $espnToTeamMappings = [
        'Spurs' => 'San Antonio Spurs',
        'Hawks' => 'Atlanta Hawks',
        'Celtics' => 'Boston Celtics',
        'Nets' => 'Brooklyn Nets',
        'Hornets' => 'Charlotte Hornets',
        'Bulls' => 'Chicago Bulls',
        'Cavaliers' => 'Cleveland Cavaliers',
        'Mavericks' => 'Dallas Mavericks',
        'Nuggets' => 'Denver Nuggets',
        'Pistons' => 'Detroit Pistons',
        'Warriors' => 'Golden State Warriors',
        'Rockets' => 'Houston Rockets',
        'Pacers' => 'Indiana Pacers',
        'Clippers' => 'LA Clippers',
        'Lakers' => 'Los Angeles Lakers',
        'Grizzlies' => 'Memphis Grizzlies',
        'Heat' => 'Miami Heat',
        'Bucks' => 'Milwaukee Bucks',
        'Timberwolves' => 'Minnesota Timberwolves',
        'Pelicans' => 'New Orleans Pelicans',
        'Knicks' => 'New York Knicks',
        'Thunder' => 'Oklahoma City Thunder',
        'Magic' => 'Orlando Magic',
        '76ers' => 'Philadelphia 76ers',
        'Suns' => 'Phoenix Suns',
        'Trail Blazers' => 'Portland Trail Blazers',
        'Kings' => 'Sacramento Kings',
        'Raptors' => 'Toronto Raptors',
        'Jazz' => 'Utah Jazz',
        'Wizards' => 'Washington Wizards',
    ];

    public function getTeamName(string $espnName): ?string
    {
        $espnName = trim($espnName);
        
        // First try direct lookup (case insensitive)
        foreach ($this->espnToTeamMappings as $shortName => $fullName) {
            if (strcasecmp($espnName, $shortName) === 0) {
                return $fullName;
            }
        }

        // Then try to find a partial match in either direction
        foreach ($this->espnToTeamMappings as $shortName => $fullName) {
            // Check if ESPN name contains our short name
            if (stripos($espnName, $shortName) !== false) {
                return $fullName;
            }
            // Check if our full name contains the ESPN name
            if (stripos($fullName, $espnName) !== false) {
                return $fullName;
            }
        }

        return null;
    }
} 