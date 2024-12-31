<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sport }} Odds Dashboard - Coming Soon</title>
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
