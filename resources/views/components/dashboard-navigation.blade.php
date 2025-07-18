<!-- resources/views/components/dashboard-navigation.blade.php -->
@props(['currentPage' => null])

<div class="mb-6 flex justify-between items-center">
    <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800">← Back to Home</a>
    <div class="space-x-4">
        <a href="{{ route('dashboard.nfl') }}"
           class="{{ $currentPage === 'nfl' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-gray-800' }}">
            NFL
        </a>
        <a href="{{ route('dashboard.ncaaf') }}"
           class="{{ $currentPage === 'ncaaf' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-gray-800' }}">
            NCAAF
        </a>
        <a href="{{ route('dashboard.ncaab') }}"
           class="{{ $currentPage === 'ncaab' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-gray-800' }}">
            NFL
        </a>
        <a href="{{ route('dashboard.nba') }}"
           class="{{ $currentPage === 'nba' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-gray-800' }}">
            NBA
        </a>
        <a href="{{ route('dashboard.mlb') }}"
           class="{{ $currentPage === 'mlb' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-gray-800' }}">
            MLB
        </a>
        <a href="{{ route('dashboard.nhl') }}"
           class="{{ $currentPage === 'nhl' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-gray-800' }}">
            NHL
        </a>
        <a href="{{ route('accuweather.analysis') }}"
           class="{{ $currentPage === 'accuweather' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
            Weather Analysis
        </a>
        <a href="{{ route('nws.analysis') }}"
           class="{{ $currentPage === 'nws-analysis' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
            NWS Weather Analysis
        </a>
        <a href="{{ route('dashboard.weather') }}"
           class="{{ $currentPage === 'weather' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
            Weather Dashboard
        </a>
        <a href="{{ route('dashboard.nws-weather') }}"
           class="{{ $currentPage === 'nws-weather' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
            NWS Weather Dashboard
        </a>
        <a href="{{ route('dashboard.combined-weather') }}"
           class="{{ $currentPage === 'combined-weather' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
            Combined Weather Dashboard
        </a>
    </div>
</div>
