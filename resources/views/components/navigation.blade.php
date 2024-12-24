<!-- resources/views/components/navigation.blade.php -->
@props(['currentPage' => null])

<nav class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <a href="{{ route('home') }}" class="text-xl font-bold text-blue-600">Smart Betting Analytics</a>
            <div class="space-x-4">
                <a href="{{ route('dashboard.nfl') }}"
                   class="{{ $currentPage === 'nfl' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
                    NFL
                </a>
                <a href="{{ route('dashboard.ncaaf') }}"
                   class="{{ $currentPage === 'ncaaf' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
                    NCAAF
                </a>
                <a href="{{ route('dashboard.nba') }}"
                   class="{{ $currentPage === 'nba' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
                    NBA
                </a>
                <a href="{{ route('dashboard.mlb') }}"
                   class="{{ $currentPage === 'mlb' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
                    MLB
                </a>
                <a href="{{ route('dashboard.nhl') }}"
                   class="{{ $currentPage === 'nhl' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
                    NHL
                </a>
            </div>
        </div>
    </div>
</nav>
