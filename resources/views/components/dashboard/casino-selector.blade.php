{{-- resources/views/components/dashboard/casino-selector.blade.php --}}
@props(['availableCasinos', 'selectedCasinos', 'sport'])

@php
    // Map sport names to route names
    $routeMap = [
        'NCAAF' => 'dashboard.ncaaf',
        'NCAAB' => 'dashboard.ncaab',
        'NFL' => 'dashboard.nfl',
        'NBA' => 'dashboard.nba',
        'MLB' => 'dashboard.mlb',
        'NHL' => 'dashboard.nhl'
    ];

    $routeName = $routeMap[strtoupper($sport)] ?? 'dashboard.index';
@endphp

<div class="mb-6 bg-white rounded-lg shadow-md p-4">
    <button type="button"
            class="w-full flex justify-between items-center text-sm font-medium text-gray-700 mb-2"
            onclick="toggleCasinoSelector()">
        <span>Select Bookmakers (Max 3)</span>
        <svg id="selector-arrow" class="w-5 h-5 transform rotate-0 transition-transform duration-200"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <form id="casino-selector-form" action="{{ route($routeName) }}" method="GET" class="hidden">
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
