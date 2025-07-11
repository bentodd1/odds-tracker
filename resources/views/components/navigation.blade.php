<!-- resources/views/components/navigation.blade.php -->
@props(['currentPage' => null])

<nav class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <a href="{{ route('home') }}" class="text-xl font-bold text-blue-600">Smart Betting Analytics</a>

            <div class="flex items-center space-x-4">
                <div class="space-x-4">
                    <a href="{{ route('dashboard.nfl') }}"
                       class="{{ $currentPage === 'nfl' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
                        NFL
                    </a>
                    <a href="{{ route('dashboard.ncaaf') }}"
                       class="{{ $currentPage === 'ncaaf' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
                        NCAAF
                    </a>
                    <a href="{{ route('dashboard.ncaab') }}"
                       class="{{ $currentPage === 'ncaab' ? 'text-blue-600 font-bold' : 'text-gray-600 hover:text-blue-600' }}">
                        NCAAB
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

                <div class="flex items-center space-x-4 ml-4">
                    @auth
                        @unless(auth()->user()->hasActiveSubscription())
                            <a href="{{ route('dashboard.subscribe') }}"
                               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                               Unlock All Odds ($10)
                            </a>
                        @endunless
                        <span class="text-gray-600">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-blue-600">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="text-gray-600 hover:text-blue-600">Login</a>
                        <a href="{{ route('register') }}"
                           class="text-blue-600 hover:text-blue-700">Sign Up to Unlock</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</nav>
