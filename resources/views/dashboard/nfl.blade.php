<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NFL Odds Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <!-- Navigation -->
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800">← Back to Home</a>
        <div class="space-x-4">
            <a href="{{ route('dashboard.nfl') }}" class="font-bold text-blue-600">NFL</a>
            <a href="{{ route('dashboard.nba') }}" class="text-gray-600 hover:text-gray-800">NBA</a>
            <a href="{{ route('dashboard.mlb') }}" class="text-gray-600 hover:text-gray-800">MLB</a>
            <a href="{{ route('dashboard.nhl') }}" class="text-gray-600 hover:text-gray-800">NHL</a>
        </div>
    </div>

    @foreach($games as $game)
        <div class="bg-white rounded-lg shadow-md mb-6 p-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">
                    {{ $game->awayTeam->name }} @ {{ $game->homeTeam->name }}
                </h2>
                <span class="text-gray-600">
                        {{ $game->commence_time->format('M j, Y g:i A') }}
                    </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- FPI Analysis -->
                <div>
                    <h3 class="font-semibold mb-3">FPI Analysis</h3>
                    @php
                        $homeTeamFpi = $game->homeTeam->latestFpi()->first();
                        $awayTeamFpi = $game->awayTeam->latestFpi()->first();

                        if ($homeTeamFpi && $awayTeamFpi) {
                            $fpiDiff = $homeTeamFpi->rating - $awayTeamFpi->rating + 2;
                            $spread = -$fpiDiff;
                            $tempSpread = new \App\Models\Spread([
                                'spread' => $spread
                            ]);
                            $probability = $tempSpread->getCoverProbabilityAttribute();
                        }
                    @endphp

                    @if(isset($probability))
                        <div class="mb-4">
                            <div class="mb-2 text-sm text-gray-600">
                                Projected Spread & Win Probability
                            </div>
                            <div>
                                <div class="mb-2">
                                    <span class="font-medium">{{ $game->homeTeam->name }}</span>
                                    <br>
                                    Spread: {{ $fpiDiff > 0 ? '-' : '+' }}{{ number_format(abs($fpiDiff), 1) }}
                                    <br>
                                    Win: {{ number_format($probability, 1) }}%
                                    <br>
                                    FPI: {{ number_format($homeTeamFpi->rating, 1) }}
                                </div>
                                <div>
                                    <span class="font-medium">{{ $game->awayTeam->name }}</span>
                                    <br>
                                    Spread: {{ -$fpiDiff > 0 ? '+' : '-' }}{{ number_format(abs($fpiDiff), 1) }}
                                    <br>
                                    Win: {{ number_format(100 - $probability, 1) }}%
                                    <br>
                                    FPI: {{ number_format($awayTeamFpi->rating, 1) }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-gray-500">FPI data not available</div>
                    @endif
                </div>

                <!-- Spreads -->
                <div>
                    <h3 class="font-semibold mb-3">Spreads</h3>
                    @php
                        // Get all probabilities for the favorite and underdog
                        $favoriteProbs = collect();
                        $underdogProbs = collect();

                        // Add spread probabilities
                        foreach($game->spreads as $gs) {
                            if($gs->spread < 0) {
                                $favoriteProbs->push([
                                    'type' => 'spread',
                                    'casino' => $gs->casino->name,
                                    'probability' => $gs->cover_probability_with_juice
                                ]);
                            } else {
                                $underdogProbs->push([
                                    'type' => 'spread',
                                    'casino' => $gs->casino->name,
                                    'probability' => 100 - $gs->cover_probability_with_juice
                                ]);
                            }
                        }

                        // Add moneyline probabilities
                        foreach($game->moneyLines as $ml) {
                            if($ml->home_odds < $ml->away_odds) {
                                $favoriteProbs->push([
                                    'type' => 'moneyline',
                                    'casino' => $ml->casino->name,
                                    'probability' => $ml->home_implied_probability
                                ]);
                                $underdogProbs->push([
                                    'type' => 'moneyline',
                                    'casino' => $ml->casino->name,
                                    'probability' => $ml->away_implied_probability
                                ]);
                            } else {
                                $favoriteProbs->push([
                                    'type' => 'moneyline',
                                    'casino' => $ml->casino->name,
                                    'probability' => $ml->away_implied_probability
                                ]);
                                $underdogProbs->push([
                                    'type' => 'moneyline',
                                    'casino' => $ml->casino->name,
                                    'probability' => $ml->home_implied_probability
                                ]);
                            }
                        }

                        // Get the lowest probability for each side
                        $bestFavoriteOdds = $favoriteProbs->sortBy('probability')->first();
                        $bestUnderdogOdds = $underdogProbs->sortBy('probability')->first();
                    @endphp

                    @foreach($game->spreads->groupBy('casino_id') as $casinoSpreads)
                        @php $spread = $casinoSpreads->sortByDesc('recorded_at')->first(); @endphp
                        <div class="mb-4">
                            <div class="flex justify-between">
                                <span>{{ strtolower($spread->casino->name) }}</span>
                                <span class="text-gray-600 text-sm">
                                        Updated: {{ $spread->recorded_at instanceof \Carbon\Carbon ? $spread->recorded_at->diffForHumans() : 'Unknown' }}
                                    </span>
                            </div>
                            <div>
                                <div @class([
                                        'p-2 rounded',
                                        'bg-green-100' => $spread->spread < 0 &&
                                                        $bestFavoriteOdds['type'] === 'spread' &&
                                                        $bestFavoriteOdds['casino'] === $spread->casino->name
                                    ])>
                                    {{ $spread->spread < 0 ? $game->homeTeam->name : $game->awayTeam->name }}
                                    {{ $spread->spread > 0 ? '+' : '' }}{{ $spread->spread }}
                                    ({{ number_format($spread->spread < 0 ? $spread->cover_probability_with_juice : 100 - $spread->cover_probability_with_juice, 1) }}%)
                                    @if($spread->is_key_number)
                                        <span class="text-blue-600 text-xs">Key</span>
                                    @endif
                                    <br>
                                    Odds: {{ $spread->spread < 0 ? $spread->home_odds : $spread->away_odds }}
                                </div>
                                <div @class([
                                        'p-2 rounded mt-1',
                                        'bg-green-100' => $spread->spread > 0 &&
                                                        $bestUnderdogOdds['type'] === 'spread' &&
                                                        $bestUnderdogOdds['casino'] === $spread->casino->name
                                    ])>
                                    {{ $spread->spread > 0 ? $game->homeTeam->name : $game->awayTeam->name }}
                                    {{ -$spread->spread > 0 ? '+' : '' }}{{ -$spread->spread }}
                                    ({{ number_format($spread->spread > 0 ? $spread->cover_probability_with_juice : 100 - $spread->cover_probability_with_juice, 1) }}%)
                                    <br>
                                    Odds: {{ $spread->spread > 0 ? $spread->home_odds : $spread->away_odds }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Money Lines -->
                <div>
                    <h3 class="font-semibold mb-3">Money Lines</h3>
                    @foreach($game->moneyLines->groupBy('casino_id') as $casinoMoneyLines)
                        @php $moneyLine = $casinoMoneyLines->sortByDesc('recorded_at')->first(); @endphp
                        <div class="mb-4">
                            <div class="flex justify-between">
                                <span>{{ strtolower($moneyLine->casino->name) }}</span>
                                <span class="text-gray-600 text-sm">
                                        Updated: {{ $moneyLine->recorded_at instanceof \Carbon\Carbon ? $moneyLine->recorded_at->diffForHumans() : 'Unknown' }}
                                    </span>
                            </div>
                            <div>
                                <div @class([
                                        'p-2 rounded',
                                        'bg-green-100' => $moneyLine->home_odds < $moneyLine->away_odds &&
                                                        $bestFavoriteOdds['type'] === 'moneyline' &&
                                                        $bestFavoriteOdds['casino'] === $moneyLine->casino->name
                                    ])>
                                    {{ $moneyLine->home_odds < $moneyLine->away_odds ? $game->homeTeam->name : $game->awayTeam->name }}
                                    <br>
                                    Odds: {{ $moneyLine->home_odds < $moneyLine->away_odds ? $moneyLine->home_odds : $moneyLine->away_odds }}
                                    ({{ number_format($moneyLine->home_odds < $moneyLine->away_odds ? $moneyLine->home_implied_probability : $moneyLine->away_implied_probability, 1) }}%)
                                </div>
                                <div @class([
                                        'p-2 rounded mt-1',
                                        'bg-green-100' => $moneyLine->home_odds > $moneyLine->away_odds &&
                                                        $bestUnderdogOdds['type'] === 'moneyline' &&
                                                        $bestUnderdogOdds['casino'] === $moneyLine->casino->name
                                    ])>
                                    {{ $moneyLine->home_odds > $moneyLine->away_odds ? $game->homeTeam->name : $game->awayTeam->name }}
                                    <br>
                                    Odds: {{ $moneyLine->home_odds > $moneyLine->away_odds ? $moneyLine->home_odds : $moneyLine->away_odds }}
                                    ({{ number_format($moneyLine->home_odds > $moneyLine->away_odds ? $moneyLine->home_implied_probability : $moneyLine->away_implied_probability, 1) }}%)
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
</body>
</html>
