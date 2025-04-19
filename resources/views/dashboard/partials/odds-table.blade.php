{{-- resources/views/dashboard/partials/odds-table.blade.php --}}
<style>
    .blur-odds {
        filter: blur(4px);
        user-select: none;
        pointer-events: none;
    }
</style>

{{-- Debug output --}}
@if(auth()->check())
    <div class="p-2 text-sm">
        Subscription status: {{ auth()->user()->hasActiveSubscription() ? 'Active' : 'Inactive' }}
    </div>
@else
    <div class="p-2 text-sm">
        User not logged in
    </div>
@endif

<div class="bg-white rounded-lg shadow-md overflow-x-auto">
    <table class="w-full min-w-[1200px]">
        <thead>
        <tr class="bg-gray-100">
            <th class="p-2 text-left">Time</th>
            <th class="p-2 text-left">Teams</th>
            <th class="p-2 text-center">FPI (Win %)</th>
            @foreach($selectedCasinos as $casinoName)
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
        @foreach($games as $index => $game)
            <!-- Away Team Row -->
            <tr class="border-t">
                <td rowspan="2" class="p-2 align-middle">
                    {{ \Carbon\Carbon::parse($game['commence_time'])->format('n/j g:i A') }}
                </td>
                <td class="p-2">
                    <div class="font-medium">{{ $game['away_team']['name'] }}</div>
                </td>
                <td class="p-2 text-center {{ (!auth()->user()?->hasActiveSubscription() && $loop->index > 0) ? 'blur-odds' : '' }}">
                    <div>
                        {{ $game['away_team']['fpi'] ? number_format($game['away_team']['fpi'], 1) : 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ $game['away_team']['win_probability'] ? number_format($game['away_team']['win_probability'], 1) . '%' : 'N/A' }}
                    </div>
                    @if(!is_null($game['away_team']['ev_value']))
                        <div class="text-xs mt-1 px-2 py-1 rounded-md inline-block
                            {{ $game['away_team']['ev_value'] >= 0 ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                            EV: {{ number_format($game['away_team']['ev_value'], 1) }}%
                        </div>
                    @endif
                </td>
                @foreach($selectedCasinos as $casinoName)
                    <td class="p-2 {{ (!auth()->user()?->hasActiveSubscription() && $loop->parent->index > 0) ? 'blur-odds' : '' }}">
                        <div class="flex text-sm">
                            <div class="flex-1 text-center">
                                @if(isset($game['casinos'][$casinoName]['spread']['away']))
                                    @php
                                        $currentProb = $game['casinos'][$casinoName]['spread']['away']['probability'];
                                        $isBestOdds = $game['away_team']['best_value']['casino'] === $casinoName && 
                                                     $game['away_team']['best_value']['type'] === 'spread';
                                        
                                        // Find highest probability among all casinos for this team (both spread and ML)
                                        $highestProb = $currentProb;
                                        foreach ($selectedCasinos as $otherCasino) {
                                            // Check spread probabilities
                                            if (isset($game['casinos'][$otherCasino]['spread']['away']['probability'])) {
                                                $otherProb = $game['casinos'][$otherCasino]['spread']['away']['probability'];
                                                $highestProb = max($highestProb, $otherProb);
                                            }
                                            // Check moneyline probabilities
                                            if (isset($game['casinos'][$otherCasino]['moneyLine']['away']['probability'])) {
                                                $otherProb = $game['casinos'][$otherCasino]['moneyLine']['away']['probability'];
                                                $highestProb = max($highestProb, $otherProb);
                                            }
                                        }
                                        
                                        // Determine if odds are significantly better (>4% lower)
                                        $isSignificantlyBetter = $currentProb + 4 < $highestProb;
                                    @endphp
                                    <div class="{{ $isBestOdds ? ($isSignificantlyBetter ? 'bg-blue-100' : 'bg-green-100') : '' }} rounded p-1">
                                        <div>
                                            {{ $game['casinos'][$casinoName]['spread']['away']['line'] > 0 ? '+' : '' }}
                                            {{ $game['casinos'][$casinoName]['spread']['away']['line'] }}
                                        </div>
                                        <div class="text-gray-600">
                                            {{ $game['casinos'][$casinoName]['spread']['away']['odds'] }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ number_format($currentProb, 1) }}%
                                        </div>
                                    </div>
                                @else
                                    <div class="text-gray-400">N/A</div>
                                @endif
                            </div>
                            <div class="flex-1 text-center">
                                @if(isset($game['casinos'][$casinoName]['moneyLine']['away']))
                                    @php
                                        $currentProb = $game['casinos'][$casinoName]['moneyLine']['away']['probability'];
                                        $isBestOdds = $game['away_team']['best_value']['casino'] === $casinoName && 
                                                     $game['away_team']['best_value']['type'] === 'moneyline';
                                        
                                        // Find highest probability among all casinos for this team (both spread and ML)
                                        $highestProb = $currentProb;
                                        foreach ($selectedCasinos as $otherCasino) {
                                            // Check spread probabilities
                                            if (isset($game['casinos'][$otherCasino]['spread']['away']['probability'])) {
                                                $otherProb = $game['casinos'][$otherCasino]['spread']['away']['probability'];
                                                $highestProb = max($highestProb, $otherProb);
                                            }
                                            // Check moneyline probabilities
                                            if (isset($game['casinos'][$otherCasino]['moneyLine']['away']['probability'])) {
                                                $otherProb = $game['casinos'][$otherCasino]['moneyLine']['away']['probability'];
                                                $highestProb = max($highestProb, $otherProb);
                                            }
                                        }
                                        
                                        // Determine if odds are significantly better (>4% lower)
                                        $isSignificantlyBetter = $currentProb + 4 < $highestProb;
                                    @endphp
                                    <div class="{{ $isBestOdds ? ($isSignificantlyBetter ? 'bg-blue-100' : 'bg-green-100') : '' }} rounded p-1">
                                        <div>
                                            {{ $game['casinos'][$casinoName]['moneyLine']['away']['odds'] > 0 ? '+' : '' }}
                                            {{ $game['casinos'][$casinoName]['moneyLine']['away']['odds'] }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ number_format($currentProb, 1) }}%
                                        </div>
                                    </div>
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
                <td class="p-2 text-center {{ (!auth()->user()?->hasActiveSubscription() && $loop->index > 0) ? 'blur-odds' : '' }}">
                    <div>
                        {{ $game['home_team']['fpi'] ? number_format($game['home_team']['fpi'], 1) : 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ $game['home_team']['win_probability'] ? number_format($game['home_team']['win_probability'], 1) . '%' : 'N/A' }}
                    </div>
                    @if(!is_null($game['home_team']['ev_value']))
                        <div class="text-xs mt-1 px-2 py-1 rounded-md inline-block
                            {{ $game['home_team']['ev_value'] >= 0 ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                            EV: {{ number_format($game['home_team']['ev_value'], 1) }}%
                        </div>
                    @endif
                </td>
                @foreach($selectedCasinos as $casinoName)
                    <td class="p-2 {{ (!auth()->user()?->hasActiveSubscription() && $loop->parent->index > 0) ? 'blur-odds' : '' }}">
                        <div class="flex text-sm">
                            <div class="flex-1 text-center">
                                @if(isset($game['casinos'][$casinoName]['spread']['home']))
                                    @php
                                        $currentProb = $game['casinos'][$casinoName]['spread']['home']['probability'];
                                        $isBestOdds = $game['home_team']['best_value']['casino'] === $casinoName && 
                                                     $game['home_team']['best_value']['type'] === 'spread';
                                        
                                        // Find highest probability among all casinos for this team (both spread and ML)
                                        $highestProb = $currentProb;
                                        foreach ($selectedCasinos as $otherCasino) {
                                            // Check spread probabilities
                                            if (isset($game['casinos'][$otherCasino]['spread']['home']['probability'])) {
                                                $otherProb = $game['casinos'][$otherCasino]['spread']['home']['probability'];
                                                $highestProb = max($highestProb, $otherProb);
                                            }
                                            // Check moneyline probabilities
                                            if (isset($game['casinos'][$otherCasino]['moneyLine']['home']['probability'])) {
                                                $otherProb = $game['casinos'][$otherCasino]['moneyLine']['home']['probability'];
                                                $highestProb = max($highestProb, $otherProb);
                                            }
                                        }
                                        
                                        // Determine if odds are significantly better (>4% lower)
                                        $isSignificantlyBetter = $currentProb + 4 < $highestProb;
                                    @endphp
                                    <div class="{{ $isBestOdds ? ($isSignificantlyBetter ? 'bg-blue-100' : 'bg-green-100') : '' }} rounded p-1">
                                        <div>
                                            {{ $game['casinos'][$casinoName]['spread']['home']['line'] > 0 ? '+' : '' }}
                                            {{ $game['casinos'][$casinoName]['spread']['home']['line'] }}
                                        </div>
                                        <div class="text-gray-600">
                                            {{ $game['casinos'][$casinoName]['spread']['home']['odds'] }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ number_format($currentProb, 1) }}%
                                        </div>
                                    </div>
                                @else
                                    <div class="text-gray-400">N/A</div>
                                @endif
                            </div>
                            <div class="flex-1 text-center">
                                @if(isset($game['casinos'][$casinoName]['moneyLine']['home']))
                                    @php
                                        $currentProb = $game['casinos'][$casinoName]['moneyLine']['home']['probability'];
                                        $isBestOdds = $game['home_team']['best_value']['casino'] === $casinoName && 
                                                     $game['home_team']['best_value']['type'] === 'moneyline';
                                        
                                        // Find highest probability among all casinos for this team (both spread and ML)
                                        $highestProb = $currentProb;
                                        foreach ($selectedCasinos as $otherCasino) {
                                            // Check spread probabilities
                                            if (isset($game['casinos'][$otherCasino]['spread']['home']['probability'])) {
                                                $otherProb = $game['casinos'][$otherCasino]['spread']['home']['probability'];
                                                $highestProb = max($highestProb, $otherProb);
                                            }
                                            // Check moneyline probabilities
                                            if (isset($game['casinos'][$otherCasino]['moneyLine']['home']['probability'])) {
                                                $otherProb = $game['casinos'][$otherCasino]['moneyLine']['home']['probability'];
                                                $highestProb = max($highestProb, $otherProb);
                                            }
                                        }
                                        
                                        // Determine if odds are significantly better (>4% lower)
                                        $isSignificantlyBetter = $currentProb + 4 < $highestProb;
                                    @endphp
                                    <div class="{{ $isBestOdds ? ($isSignificantlyBetter ? 'bg-blue-100' : 'bg-green-100') : '' }} rounded p-1">
                                        <div>
                                            {{ $game['casinos'][$casinoName]['moneyLine']['home']['odds'] > 0 ? '+' : '' }}
                                            {{ $game['casinos'][$casinoName]['moneyLine']['home']['odds'] }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ number_format($currentProb, 1) }}%
                                        </div>
                                    </div>
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

    @unless(auth()->user()?->hasActiveSubscription())
        <div class="text-center py-4 bg-blue-50">
            <p class="text-gray-600 mb-2">Subscribe to see all odds and analytics</p>
            <a href="{{ route('dashboard.subscribe') }}" 
               class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                Unlock All Games ($10)
            </a>
        </div>
    @endunless
</div>
