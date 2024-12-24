<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NCAAF Odds Dashboard - Coming Soon</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <!-- Navigation -->
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800">← Back to Home</a>
        <div class="space-x-4">
            <a href="{{ route('dashboard.nfl') }}" class="text-gray-600 hover:text-gray-800">NFL</a>
            <a href="{{ route('dashboard.ncaaf') }}" class="font-bold text-blue-600">NCAAF</a>
            <a href="{{ route('dashboard.nba') }}" class="text-gray-600 hover:text-gray-800">NBA</a>
            <a href="{{ route('dashboard.mlb') }}" class="text-gray-600 hover:text-gray-800">MLB</a>
            <a href="{{ route('dashboard.nhl') }}" class="text-gray-600 hover:text-gray-800">NHL</a>
        </div>
    </div>

    <!-- Coming Soon Content -->
    <div class="max-w-2xl mx-auto mt-16">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">NCAAF Dashboard Coming Soon</h1>
            <div class="bg-blue-50 rounded-lg p-6 mb-6">
                <p class="text-gray-700 mb-4">
                    We're working hard to bring you comprehensive NCAAF betting analytics. Our dashboard will include:
                </p>
                <ul class="text-left text-gray-600 space-y-3 max-w-md mx-auto">
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        FPI-based predictions for all games
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Cross-sportsbook value comparison
                    </li>
                    <li class="flex items-start">
                        <svg class="h-6 w-6 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Key number analysis for college spreads
                    </li>
                </ul>
            </div>
            <div class="text-gray-600">
                <p>Expected launch: 2024 Season</p>
                <p class="mt-2 text-sm">Check back soon for updates!</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
