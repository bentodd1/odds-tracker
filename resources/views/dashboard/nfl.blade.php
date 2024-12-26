<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sport }} Odds Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <!-- Navigation -->
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800">← Back to Home</a>
        <div class="space-x-4">
            <a href="{{ route('dashboard.nfl') }}"
               class="{{ $sport === 'NFL' ? 'font-bold text-blue-600' : 'text-gray-600 hover:text-gray-800' }}">NFL</a>
            <a href="{{ route('dashboard.ncaaf') }}"
               class="{{ $sport === 'NCAAF' ? 'font-bold text-blue-600' : 'text-gray-600 hover:text-gray-800' }}">NCAAF</a>
            <a href="{{ route('dashboard.nba') }}"
               class="{{ $sport === 'NBA' ? 'font-bold text-blue-600' : 'text-gray-600 hover:text-gray-800' }}">NBA</a>
            <a href="{{ route('dashboard.mlb') }}"
               class="{{ $sport === 'MLB' ? 'font-bold text-blue-600' : 'text-gray-600 hover:text-gray-800' }}">MLB</a>
            <a href="{{ route('dashboard.nhl') }}"
               class="{{ $sport === 'NHL' ? 'font-bold text-blue-600' : 'text-gray-600 hover:text-gray-800' }}">NHL</a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="w-full min-w-[1200px]">
            <thead>
            <tr class="bg-gray-100">
                <th class="p-2 text-left">Time</th>
                <th class="p-2 text-left">Teams</th>
                <th class="p-2 text-center">FPI (Win %)</th>
                @foreach($games->first()['casinos'] as $casinoName => $casinoData)
                    <th class="p-2 text-center">
                        <div>{{ ucfirst($casinoName) }}</div>
                        <div class="flex text-sm">
                            <span class="flex-1">Spread</span>
                            <span class="flex-1">ML</span>
                        </div>
                    </th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($games as $game)
                <!-- Away Team Row -->
                <tr class="border-t">
                    <td rowspan="2" class="p-2 align-middle">
                        {{ Carbon\Carbon::parse($game['commence_time'])->format('n/j g:i A') }}
                    </td>
                    <td class="p-2">
                        <div class="font-medium">{{ $game['away_team']['name'] }}</div>
                    </td>
                    <td class="p-2 text-center">
                        <div>{{ $game['away_team']['fpi'] ? number_format($game['away_team']['fpi'], 1) : 'N/A' }}</div>
                        <div class="text-sm text-gray-600">
                            {{ $game['away_team']['win_probability'] ? number_format($game['away_team']['win_probability'], 1) . '%' : 'N/A' }}
                        </div>
                    </td>
                    @foreach($game['casinos'] as $casinoName => $casinoData)
                        <td class="p-2">
                            <div class="flex text-sm">
                                <div class="flex-1 text-center {{ in_array($casinoName, $game['away_team']['best_value_casinos']) ? 'bg-green-100 rounded p-1' : '' }}">
                                    <div>{{ $casinoData['spread']['away']['line'] > 0 ? '+' : '' }}{{ $casinoData['spread']['away']['line'] }}</div>
                                    <div class="text-gray-600">{{ $casinoData['spread']['away']['odds'] }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format($casinoData['spread']['away']['probability'], 1) }}%</div>
                                </div>
                                <div class="flex-1 text-center {{ in_array($casinoName, $game['away_team']['best_value_casinos']) && isset($casinoData['moneyLine']) && $casinoData['moneyLine']['away']['probability'] <= $casinoData['spread']['away']['probability'] ? 'bg-green-100 rounded p-1' : '' }}">
                                    @if(isset($casinoData['moneyLine']))
                                        <div>{{ $casinoData['moneyLine']['away']['odds'] > 0 ? '+' : '' }}{{ $casinoData['moneyLine']['away']['odds'] }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($casinoData['moneyLine']['away']['probability'], 1) }}%</div>
                                    @else
                                        <div class="text-gray-400">N/A</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>

                <!-- Home Team Row -->
                <tr class="border-b bg-gray-50">
                    <td class="p-2">
                        <div class="font-medium">{{ $game['home_team']['name'] }}</div>
                    </td>
                    <td class="p-2 text-center">
                        <div>{{ $game['home_team']['fpi'] ? number_format($game['home_team']['fpi'], 1) : 'N/A' }}</div>
                        <div class="text-sm text-gray-600">
                            {{ $game['home_team']['win_probability'] ? number_format($game['home_team']['win_probability'], 1) . '%' : 'N/A' }}
                        </div>
                    </td>
                    @foreach($game['casinos'] as $casinoName => $casinoData)
                        <td class="p-2">
                            <div class="flex text-sm">
                                <div class="flex-1 text-center {{ in_array($casinoName, $game['home_team']['best_value_casinos']) ? 'bg-green-100 rounded p-1' : '' }}">
                                    <div>{{ $casinoData['spread']['home']['line'] > 0 ? '+' : '' }}{{ $casinoData['spread']['home']['line'] }}</div>
                                    <div class="text-gray-600">{{ $casinoData['spread']['home']['odds'] }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format($casinoData['spread']['home']['probability'], 1) }}%</div>
                                </div>
                                <div class="flex-1 text-center {{ in_array($casinoName, $game['home_team']['best_value_casinos']) && isset($casinoData['moneyLine']) && $casinoData['moneyLine']['home']['probability'] <= $casinoData['spread']['home']['probability'] ? 'bg-green-100 rounded p-1' : '' }}">
                                    @if(isset($casinoData['moneyLine']))
                                        <div>{{ $casinoData['moneyLine']['home']['odds'] > 0 ? '+' : '' }}{{ $casinoData['moneyLine']['home']['odds'] }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($casinoData['moneyLine']['home']['probability'], 1) }}%</div>
                                    @else
                                        <div class="text-gray-400">N/A</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
