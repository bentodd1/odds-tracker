<!-- resources/views/home.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Betting Analytics - Make Better Decisions</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<x-navigation :currentPage="null" />

<!-- Hero Section -->
<div class="bg-white">
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-4xl font-bold mb-6">Know What You're Really Paying For</h1>
            <p class="text-xl text-gray-600 mb-8">
                We translate confusing spreads and money lines into clear probabilities, compare them against analytical predictions (FPI), and help you find the best value across sportsbooks.
            </p>
            <div class="flex justify-center gap-4">
                <a href="{{ route('dashboard.nfl') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">View NFL Odds</a>
                <a href="#features" class="border border-blue-600 text-blue-600 px-6 py-3 rounded-lg hover:bg-blue-50 transition">Learn More</a>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div id="features" class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">How We Help You Make Better Decisions</h2>

        <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
            <div class="bg-white p-8 rounded-lg shadow">
                <div class="text-blue-600 text-xl font-semibold mb-4">Understanding True Odds</div>
                <p class="text-gray-600 mb-4">Betting lines can be confusing. What does -110 really mean? What about -14.5 with -105? We translate these numbers into clear probabilities so you know exactly what the sportsbook thinks will happen.</p>
                <p class="text-gray-600">For example, when you see:</p>
                <ul class="list-disc list-inside text-gray-600 mt-2 space-y-2">
                    <li>Spread: -14.5 (-110) → We show: 86.7% win probability (based on spread size)</li>
                    <li>Money Line: -950 → We show: 90.5% probability</li>
                    <li>Here, the spread is clearly better value.</li>
                </ul>
            </div>

            <div class="bg-white p-8 rounded-lg shadow">
                <div class="text-blue-600 text-xl font-semibold mb-4">Finding the Best Value</div>
                <p class="text-gray-600 mb-4">We highlight the lowest implied probability across all sportsbooks for each team. Lower implied probability means better potential value for you.</p>
                <p class="text-gray-600">We also show you:</p>
                <ul class="list-disc list-inside text-gray-600 mt-2 space-y-2">
                    <li>FPI predictions for what the odds "should" be</li>
                    <li>Highlighted best values across all books</li>
                    <li>Key numbers for spread betting</li>
                </ul>
            </div>
        </div>

        <div class="mt-12 max-w-3xl mx-auto bg-blue-50 p-8 rounded-lg">
            <div class="text-blue-600 text-xl font-semibold mb-4">Why This Matters</div>
            <p class="text-gray-600 mb-4">Different sportsbooks often have different odds for the same outcome. A team might have an 82% implied probability at one book but 79% at another. That 3% difference is your edge - getting the best price means better value for your money.</p>
            <p class="text-gray-600">Our dashboard automatically finds these discrepancies and highlights the best values, saving you time and helping you make more informed decisions.</p>
        </div>
    </div>
</div>

<!-- Responsible Gambling Section -->
<div class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold text-center mb-8">Betting Responsibly</h2>

            <div class="space-y-6">
                <div class="bg-blue-50 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold mb-3">Set Clear Limits</h3>
                    <p class="text-gray-600">Decide on a fixed bankroll and stick to it. Never bet more than you can afford to lose.</p>
                </div>

                <div class="bg-blue-50 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold mb-3">Use Data, Not Emotion</h3>
                    <p class="text-gray-600">Our tools help you make decisions based on statistics and analysis rather than gut feelings or chasing losses.</p>
                </div>

                <div class="bg-blue-50 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold mb-3">Know When to Step Back</h3>
                    <p class="text-gray-600">Gambling should never interfere with your personal life or financial stability. Take regular breaks and set strict time limits.</p>
                </div>
            </div>

            <div class="mt-8 text-center">
                <p class="text-gray-600 mb-4">Need help with problem gambling?</p>
                <a href="tel:1-800-522-4700" class="text-blue-600 font-semibold hover:underline">Call 1-800-522-4700</a>
                <p class="text-sm text-gray-500 mt-2">National Problem Gambling Helpline - Available 24/7</p>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-gray-800 text-white py-8">
    <div class="container mx-auto px-4">
        <div class="text-center text-sm">
            <p class="mb-2">© 2024 Smart Betting Analytics. Please bet responsibly.</p>
            <p>For entertainment purposes only. Past performance does not guarantee future results.</p>
        </div>
    </div>
</footer>
</body>
</html>
