<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sport }} Odds Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- resources/views/layouts/app.blade.php (or your layout file) -->
    <script>
        !function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.async=!0,p.src=s.api_host+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="capture identify alias people.set people.set_once set_config register register_once unregister opt_out_capturing has_opted_out_capturing opt_in_capturing reset isFeatureEnabled onFeatureFlags".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
        posthog.init('{{ config('services.posthog.key') }}',{api_host:'{{ config('services.posthog.host') }}'})
    </script>
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

    <!-- Dashboard Explanation Header (Closeable) -->
    <div id="explanation-header" class="mb-6 bg-white rounded-lg shadow-md p-4">
        <div class="flex justify-between items-start mb-4">
            <h2 class="text-xl font-semibold">How to Read This Dashboard</h2>
            <button onclick="closeExplanation()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Left side - Key Metrics -->
            <div>
                <h3 class="text-lg font-semibold mb-2">Understanding the Numbers</h3>
                <div class="space-y-3">
                    <div class="flex items-start space-x-2">
                        <div class="w-4 h-4 mt-1 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xs">1</div>
                        <div>
                            <span class="font-medium">FPI (Win %)</span>
                            <p class="text-sm text-gray-600">Analytics-based win probability prediction for each team.</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-2">
                        <div class="w-4 h-4 mt-1 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xs">2</div>
                        <div>
                            <span class="font-medium">Implied Probability</span>
                            <p class="text-sm text-gray-600">The small percentage under each betting line shows what you're "paying for". The lower this number, the better the deal you're getting. Look for lower implied probabilities when comparing the same bet across bookmakers.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side - Best Odds Indicator -->
            <div>
                <h3 class="text-lg font-semibold mb-2">Best Odds Indicator</h3>
                <div class="space-y-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 bg-green-100 rounded"></div>
                        <span class="text-sm">Highlighted cells show the best available odds for that team across all bookmakers</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">Pro Tip:</span> Compare odds across different bookmakers to find the best value for your bets.
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Legend -->
        <div class="border-t pt-3 mt-3">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="font-medium">Spread:</span> Point handicap
                </div>
                <div>
                    <span class="font-medium">ML:</span> Moneyline (straight win)
                </div>
                <div>
                    <span class="font-medium">+150:</span> Profit $150 on $100 bet
                </div>
                <div>
                    <span class="font-medium">-150:</span> Bet $150 to profit $100
                </div>
            </div>
        </div>
    </div>

    <!-- Main Odds Table -->
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
                                <div class="flex-1 text-center {{
                                            $game['away_team']['best_value']['casino'] === $casinoName &&
                                            $game['away_team']['best_value']['type'] === 'spread'
                                            ? 'bg-green-100 rounded p-1' : '' }}">
                                    <div>{{ $casinoData['spread']['away']['line'] > 0 ? '+' : '' }}{{ $casinoData['spread']['away']['line'] }}</div>
                                    <div class="text-gray-600">{{ $casinoData['spread']['away']['odds'] }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format($casinoData['spread']['away']['probability'], 1) }}%</div>
                                </div>
                                <div class="flex-1 text-center {{
                                            $game['away_team']['best_value']['casino'] === $casinoName &&
                                            $game['away_team']['best_value']['type'] === 'moneyline'
                                            ? 'bg-green-100 rounded p-1' : '' }}">
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
                                <div class="flex-1 text-center {{
                                            $game['home_team']['best_value']['casino'] === $casinoName &&
                                            $game['home_team']['best_value']['type'] === 'spread'
                                            ? 'bg-green-100 rounded p-1' : '' }}">
                                    <div>{{ $casinoData['spread']['home']['line'] > 0 ? '+' : '' }}{{ $casinoData['spread']['home']['line'] }}</div>
                                    <div class="text-gray-600">{{ $casinoData['spread']['home']['odds'] }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format($casinoData['spread']['home']['probability'], 1) }}%</div>
                                </div>
                                <div class="flex-1 text-center {{
                                            $game['home_team']['best_value']['casino'] === $casinoName &&
                                            $game['home_team']['best_value']['type'] === 'moneyline'
                                            ? 'bg-green-100 rounded p-1' : '' }}">
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

<script>
    // Function to close the explanation header
    function closeExplanation() {
        const header = document.getElementById('explanation-header');
        header.style.display = 'none';

        // Save the state to localStorage so it stays hidden on refresh
        localStorage.setItem('explanationClosed', 'true');
    }

    // Check if the explanation should be hidden on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('explanationClosed') === 'true') {
            document.getElementById('explanation-header').style.display = 'none';
        }
    });
</script>
</body>
</html>

<!-- resources/views/layouts/app.blade.php (or your layout file) -->
<script>
    !function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.async=!0,p.src=s.api_host+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="capture identify alias people.set people.set_once set_config register register_once unregister opt_out_capturing has_opted_out_capturing opt_in_capturing reset isFeatureEnabled onFeatureFlags".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
    posthog.init('{{ config('services.posthog.key') }}',{api_host:'{{ config('services.posthog.host') }}'})
</script>
