@extends('layouts.app')

@section('title', 'Smart Betting Analytics - Make Better Decisions')

@section('content')
    <!-- Hero Section -->
    <section class="bg-white">
        <div class="container mx-auto px-4 py-16">
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl font-bold mb-6">
                    Know What You're Really Paying For
                </h1>
                <p class="text-xl text-gray-600 mb-8">
                    We turn confusing spreads and moneylines into clear probabilities,
                    compare them to analytics (FPI), and help you discover the best value
                    across top sportsbooks.
                </p>
                <div class="flex justify-center gap-4">
                    <a href="{{ route('dashboard.nfl') }}"
                       class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        View NFL Odds
                    </a>
                    <a href="#odds"
                       class="border border-blue-600 text-blue-600 px-6 py-3 rounded-lg hover:bg-blue-50 transition">
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section id="odds" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4 max-w-5xl">
            <!-- Understanding True Odds -->
            <div class="bg-white rounded-lg shadow p-8 mb-12">
                <h2 class="text-2xl font-bold text-blue-600 mb-4">
                    Understanding True Odds
                </h2>
                <p class="text-gray-600 mb-4">
                    Betting lines can be confusing: What does "-110" really mean?
                    Or "-14.5 at -105"? We convert these numbers into explicit
                    probabilities so you know how likely each bet is to win.
                </p>

                <!-- Spread-Based Probabilities -->
                <h3 class="text-xl font-semibold text-blue-500 mb-2">
                    Spread-Based Probabilities + Vig
                </h3>
                <p class="text-gray-600 mb-4">
                    First, we determine how often a -14.5 favorite would have covered
                    that spread in our dataset (for simplicity, let's say about 87%
                    of comparable scenarios). But sportsbooks also include a vig
                    (commission) in the -110 line.
                </p>

                <p class="text-gray-600 mb-4">
                    To estimate the vig for a -110 line, we do:
                </p>
                <pre class="bg-gray-100 p-4 rounded text-sm mb-4">
vig (%) = ( (110 / 210) - 0.5 ) * 100
        = (0.5238 - 0.5) * 100
        = ~2.3%
                </pre>
                <p class="text-gray-600 mb-4">
                    This 2.3% vig effectively reduces your true payout. So if the
                    baseline coverage probability is 87%, we often show
                    <strong>89.3%</strong> (87% + 2.3% = 89.3%) as the "true odds"
                    for that spread, reflecting both the underlying data and the
                    added vig.
                </p>

                <!-- Moneyline-Based Probabilities -->
                <h3 class="text-xl font-semibold text-blue-500 mb-2">
                    Moneyline-Based Probabilities
                </h3>
                <p class="text-gray-600 mb-4">
                    For negative moneylines like "-950," we use the
                    <strong>standard implied probability formula</strong>:
                </p>
                <pre class="bg-gray-100 p-4 rounded text-sm mb-4">
Probability = |moneyline| / (|moneyline| + 100)
For -950, that's 950 / (950 + 100) = 0.90476 (~90.5%)
                </pre>
                <p class="text-gray-600">
                    This tells us a -950 favorite has about a <strong>90.5% chance</strong> to win.
                </p>

                <!-- Example Comparison -->
                <div class="bg-blue-50 p-4 rounded-lg mt-6">
                    <p class="text-gray-700">
                        <strong>Example:</strong><br />
                        -14.5 at -110 → Coverage ~87% + Vig 2.3% = <strong>89.3%</strong><br />
                        -950 moneyline → Implied probability ~<strong>90.5%</strong><br />
                        By comparing these side by side, you'll know if the spread
                        or the moneyline offers a better expected return.
                    </p>
                </div>
            </div>

            <!-- Finding the Best Value Section -->
            <div class="bg-white rounded-lg shadow p-8">
                <h2 class="text-2xl font-bold text-blue-600 mb-4">
                    Finding the Best Value
                </h2>
                <p class="text-gray-600 mb-4">
                    Different sportsbooks often post slightly different odds for the same matchup.
                    That's why we highlight the most favorable line for each team—so you know right
                    away where you'll get the <strong>best price</strong>.
                </p>
                <p class="text-gray-600 mb-4">
                    The <strong>Implied Probability</strong> shown under each line reveals how much
                    you're "paying" for that bet. A lower implied probability translates to higher
                    potential returns. Compare these numbers across multiple books to pinpoint the
                    best opportunities.
                </p>
                <p class="text-gray-600 mb-4">
                    We also list each team's <strong>ESPN FPI (Win %)</strong>. According to
                    <a href="https://www.thepredictiontracker.com/" target="_blank" class="underline text-blue-600">
                        The Prediction Tracker
                    </a>, ESPN's FPI consistently performs near the top against the spread. By comparing
                    the posted lines with FPI's projected win rates, you can see which bets align best
                    with an analytics-based "fair" probability.
                </p>
                <p class="text-gray-600">
                    With all these insights—highlighted best odds, implied probabilities, and FPI
                    estimates—you can quickly identify wagers that <strong>offer true value</strong>
                    and skip the ones that might not be worth your money.
                </p>
            </div>
        </div>
    </section>

    <!-- Responsible Gambling Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 max-w-3xl">
            <h2 class="text-3xl font-bold text-center mb-8">Bet Responsibly</h2>
            <div class="space-y-6">
                <div class="bg-blue-50 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold mb-3">Set Clear Limits</h3>
                    <p class="text-gray-600">
                        Decide on a fixed bankroll and stick to it. Never bet more
                        than you can afford to lose.
                    </p>
                </div>
                <div class="bg-blue-50 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold mb-3">Use Data, Not Emotion</h3>
                    <p class="text-gray-600">
                        Our tools help you make decisions based on statistics and
                        analysis rather than gut feelings or chasing losses.
                    </p>
                </div>
                <div class="bg-blue-50 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold mb-3">Know When to Step Back</h3>
                    <p class="text-gray-600">
                        Gambling should never interfere with your personal life or
                        financial stability. Take regular breaks and set strict
                        time limits.
                    </p>
                </div>
            </div>
            <div class="mt-8 text-center">
                <p class="text-gray-600 mb-4">Need help with problem gambling?</p>
                <a href="tel:1-800-522-4700" class="text-blue-600 font-semibold hover:underline">
                    Call 1-800-522-4700
                </a>
                <p class="text-sm text-gray-500 mt-2">
                    National Problem Gambling Helpline — Available 24/7
                </p>
            </div>
        </div>
    </section>
@endsection
