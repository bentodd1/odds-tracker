@extends('layouts.app')

@section('title', 'NCAAB Odds Dashboard')

@section('head')
@endsection

@section('content')
    <div class="container mx-auto px-4 py-8">
        <!-- Dashboard Explanation Header -->
        <div id="explanation-header" class="mb-6 bg-white rounded-lg shadow-md p-4">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-xl font-semibold">How to Read This Dashboard</h2>
                <button onclick="closeExplanation()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <!-- Left side - Key Metrics -->
                <div>
                    <h3 class="text-lg font-semibold mb-2">Understanding the Numbers</h3>
                    <div class="space-y-3">
                        <div class="flex items-start space-x-2">
                            <div
                                class="w-4 h-4 mt-1 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xs">1
                            </div>
                            <div>
                                <span class="font-medium">FPI (Win %)</span>
                                <p class="text-sm text-gray-600">Analytics-based win probability prediction for each
                                    team.</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-2">
                            <div
                                class="w-4 h-4 mt-1 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xs">2
                            </div>
                            <div>
                                <span class="font-medium">Implied Probability</span>
                                <p class="text-sm text-gray-600">The small percentage under each betting line shows what
                                    you're "paying for". The lower this number, the better the deal you're getting.</p>
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
                            <span class="font-medium">Pro Tip:</span> Compare odds across different bookmakers to find
                            the best value for your bets.
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

        <!-- Casino Selector -->
        <div class="mb-6 bg-white rounded-lg shadow-md p-4">
            <button type="button"
                    class="w-full flex justify-between items-center text-sm font-medium text-gray-700 mb-2"
                    onclick="toggleCasinoSelector()">
                <span>Select Bookmakers (Max 3)</span>
                <svg id="selector-arrow" class="w-5 h-5 transform rotate-0 transition-transform duration-200"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <form id="casino-selector-form" action="{{ route('dashboard.nfl') }}" method="GET" class="hidden">
                <div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 mb-4">
                        @foreach($availableCasinos as $casino)
                            <button type="button"
                                    class="casino-btn p-2 border rounded-md text-center {{ in_array($casino->name, $selectedCasinos) ? 'bg-blue-100 border-blue-500' : 'bg-white border-gray-300' }}"
                                    data-casino="{{ $casino->name }}">
                                {{ ucfirst($casino->name) }}
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="casinos" id="selected-casinos" value="{{ implode(',', $selectedCasinos) }}">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Update View
                    </button>
                </div>
            </form>
        </div>

        <!-- Main Odds Table -->
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
                @foreach($games as $game)
                    <!-- Away Team Row -->
                    <tr class="border-t">
                        <td rowspan="2" class="p-2 align-middle">
                            {{ \Carbon\Carbon::parse($game['commence_time'])->format('n/j g:i A') }}
                        </td>
                        <td class="p-2">
                            <div class="font-medium">{{ $game['away_team']['name'] }}</div>
                        </td>
                        <td class="p-2 text-center">
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
                            <td class="p-2">
                                <div class="flex text-sm">
                                    <div class="flex-1 text-center {{
                                                $game['away_team']['best_value']['casino'] === $casinoName &&
                                                $game['away_team']['best_value']['type'] === 'spread'
                                                ? 'bg-green-100 rounded p-1' : '' }}">
                                        @if(isset($game['casinos'][$casinoName]['spread']['away']))
                                            <div>
                                                {{ $game['casinos'][$casinoName]['spread']['away']['line'] > 0 ? '+' : '' }}
                                                {{ $game['casinos'][$casinoName]['spread']['away']['line'] }}
                                            </div>
                                            <div class="text-gray-600">
                                                {{ $game['casinos'][$casinoName]['spread']['away']['odds'] }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ number_format($game['casinos'][$casinoName]['spread']['away']['probability'], 1) }}%
                                            </div>
                                        @else
                                            <div class="text-gray-400">N/A</div>
                                        @endif
                                    </div>
                                    <div class="flex-1 text-center {{
                                                $game['away_team']['best_value']['casino'] === $casinoName &&
                                                $game['away_team']['best_value']['type'] === 'moneyline'
                                                ? 'bg-green-100 rounded p-1' : '' }}">
                                        @if(isset($game['casinos'][$casinoName]['moneyLine']['away']))
                                            <div>
                                                {{ $game['casinos'][$casinoName]['moneyLine']['away']['odds'] > 0 ? '+' : '' }}
                                                {{ $game['casinos'][$casinoName]['moneyLine']['away']['odds'] }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ number_format($game['casinos'][$casinoName]['moneyLine']['away']['probability'], 1) }}%
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
                        <td class="p-2 text-center">
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
                            <td class="p-2">
                                <div class="flex text-sm">
                                    <div class="flex-1 text-center {{
                                                $game['home_team']['best_value']['casino'] === $casinoName &&
                                                $game['home_team']['best_value']['type'] === 'spread'
                                                ? 'bg-green-100 rounded p-1' : '' }}">
                                        @if(isset($game['casinos'][$casinoName]['spread']['home']))
                                            <div>
                                                {{ $game['casinos'][$casinoName]['spread']['home']['line'] > 0 ? '+' : '' }}
                                                {{ $game['casinos'][$casinoName]['spread']['home']['line'] }}
                                            </div>
                                            <div class="text-gray-600">
                                                {{ $game['casinos'][$casinoName]['spread']['home']['odds'] }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ number_format($game['casinos'][$casinoName]['spread']['home']['probability'], 1) }}%
                                            </div>
                                        @else
                                            <div class="text-gray-400">N/A</div>
                                        @endif
                                    </div>
                                    <div class="flex-1 text-center {{
                                                $game['home_team']['best_value']['casino'] === $casinoName &&
                                                $game['home_team']['best_value']['type'] === 'moneyline'
                                                ? 'bg-green-100 rounded p-1' : '' }}">
                                        @if(isset($game['casinos'][$casinoName]['moneyLine']['home']))
                                            <div>
                                                {{ $game['casinos'][$casinoName]['moneyLine']['home']['odds'] > 0 ? '+' : '' }}
                                                {{ $game['casinos'][$casinoName]['moneyLine']['home']['odds'] }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ number_format($game['casinos'][$casinoName]['moneyLine']['home']['probability'], 1) }}%
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
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.casino-btn');
            const hiddenInput = document.getElementById('selected-casinos');
            const maxSelections = 3;

            function toggleCasinoSelector() {
                const form = document.getElementById('casino-selector-form');
                const arrow = document.getElementById('selector-arrow');
                form.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
            window.toggleCasinoSelector = toggleCasinoSelector;

            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const casino = this.dataset.casino;
                    let selected = hiddenInput.value ? hiddenInput.value.split(',').filter(Boolean) : [];

                    if (this.classList.contains('bg-blue-100')) {
                        // Deselect
                        selected = selected.filter(item => item !== casino);
                        this.classList.remove('bg-blue-100', 'border-blue-500');
                        this.classList.add('bg-white', 'border-gray-300');
                    } else if (selected.length < maxSelections) {
                        // Select
                        selected.push(casino);
                        this.classList.remove('bg-white', 'border-gray-300');
                        this.classList.add('bg-blue-100', 'border-blue-500');
                    }

                    hiddenInput.value = selected.join(',');
                });
            });

            function closeExplanation() {
                const header = document.getElementById('explanation-header');
                header.style.display = 'none';
                localStorage.setItem('explanationClosed', 'true');
            }
            window.closeExplanation = closeExplanation;

            if (localStorage.getItem('explanationClosed') === 'true') {
                document.getElementById('explanation-header').style.display = 'none';
            }
        });
    </script>
@endpush
