<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports Odds Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Spreads -->
                <div>
                    <h3 class="font-semibold mb-3">Spreads</h3>
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
                                <div>
                                    {{ $game->homeTeam->name }} {{ $spread->spread > 0 ? '+' : '' }}{{ $spread->spread }} ({{ number_format($spread->cover_probability_with_juice, 1) }}%)
                                    @if($spread->is_key_number)
                                        <span class="text-blue-600 text-xs">Key</span>
                                    @endif
                                    <br>
                                    Odds: {{ $spread->home_odds }}
                                </div>
                                <div>
                                    {{ $game->awayTeam->name }} {{ -$spread->spread > 0 ? '+' : '' }}{{ -$spread->spread }} ({{ number_format(100 - $spread->cover_probability_with_juice, 1) }}%)
                                    <br>
                                    Odds: {{ $spread->away_odds }}
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
                                <div>
                                    {{ $game->homeTeam->name }}
                                    <br>
                                    Odds: {{ $moneyLine->home_odds }} ({{ number_format($moneyLine->home_implied_probability, 1) }}%)
                                </div>
                                <div>
                                    {{ $game->awayTeam->name }}
                                    <br>
                                    Odds: {{ $moneyLine->away_odds }} ({{ number_format($moneyLine->away_implied_probability, 1) }}%)
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
