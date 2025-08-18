# Sports Odds Tracker

A Laravel application that fetches and analyzes historical sports betting odds from The Odds API.

## Overview

This application tracks historical spreads and results for various sports:
- NFL (American Football)
- NCAAF (College Football)
- NBA (Basketball)
- NCAAB (College Basketball)
- MLB (Baseball)

## Requirements

- PHP 8.3+
- MySQL 5.7+
- Laravel 11.x
- The Odds API key (get one at https://the-odds-api.com)

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd odds-tracker
```

2. Install dependencies:
```bash
composer install
```

3. Set up environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure your database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=odds_tracker
DB_USERNAME=your_username
DB_PASSWORD=your_password

ODDS_API_KEY=your_api_key
```

5. Run migrations:
```bash
php artisan migrate
```

## Database Structure

### Core Tables
- `sports` - Supported sports leagues (NFL, NCAAF, NBA, NCAAB, MLB)
- `teams` - Team information
- `games` - Individual games/matches
- `casinos` - Sportsbooks/bookmakers
- `spreads` - Point spread betting lines
- `scores` - Game final scores

### Results Tables
- `spread_results` - Computed results for spread bets (home_covered, away_covered, push)

## Usage

### Sync Sports Data
```bash
php artisan odds:sync-sports
```

### Fetch Historical Spreads
```bash
# Fetch NFL spreads (default)
php artisan odds:fetch-historical-spreads americanfootball_nfl

# Fetch College Football
php artisan odds:fetch-historical-spreads americanfootball_ncaaf

# Fetch NBA
php artisan odds:fetch-historical-spreads basketball_nba

# Fetch College Basketball
php artisan odds:fetch-historical-spreads basketball_ncaab

# Fetch with specific start date
php artisan odds:fetch-historical-spreads americanfootball_nfl --start-date=2023-01-01
```

## Sport Keys
- NFL: `americanfootball_nfl`
- NCAAF: `americanfootball_ncaaf`
- NBA: `basketball_nba`
- NCAAB: `basketball_ncaab`
- MLB: `baseball_mlb`

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific tests:
```bash
php artisan test --filter=SpreadResultTest
```

### Test Coverage
- Spread calculation validation
- Home/Away team coverage scenarios
- Push scenarios
- Half-point spreads
- Underdog/Favorite scenarios
- Score validation
- Data integrity checks

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

[MIT License](LICENSE.md)

## Development Notes

This project is built with:
- Laravel 11.29
- PHP 8.3
- MySQL for data storage
- The Odds API for historical odds data

API rate limits and data availability may vary by sport and time period. Please refer to The Odds API documentation for detailed information about data availability for each sport.


New Lines
More
