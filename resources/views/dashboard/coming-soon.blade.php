<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sport }} Odds Dashboard - Coming Soon</title>
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

    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Coming Soon</h1>
        <p class="text-xl text-gray-600 mb-6">{{ $sport }} odds dashboard is currently under development.</p>
        <div class="animate-pulse flex justify-center items-center space-x-4">
            <div class="h-3 w-3 bg-blue-500 rounded-full"></div>
            <div class="h-3 w-3 bg-blue-500 rounded-full"></div>
            <div class="h-3 w-3 bg-blue-500 rounded-full"></div>
        </div>
    </div>
</div>
</body>
</html>
