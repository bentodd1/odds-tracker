{{-- resources/views/components/dashboard/explanation-header.blade.php --}}
@props(['sport'])

<div id="explanation-header" class="mb-6 bg-white rounded-lg shadow-md p-4">
    <div class="flex justify-between items-start mb-4">
        <h2 class="text-xl font-semibold">How to Read This Dashboard</h2>
        <button onclick="closeExplanation()" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Left side - Key Metrics -->
        <div>
            <h3 class="text-lg font-semibold mb-2">Understanding the Numbers</h3>
            <div class="space-y-3">
                <div class="flex items-start space-x-2">
                    <div class="w-4 h-4 mt-1 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xs">1</div>
                    <div>
                        <span class="font-medium">FPI (Win %)</span>
                        <p class="text-sm text-gray-600">Analytics-based win probability prediction for each team.</p>
                    </div>
                </div>
                <div class="flex items-start space-x-2">
                    <div class="w-4 h-4 mt-1 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xs">2</div>
                    <div>
                        <span class="font-medium">Implied Probability</span>
                        <p class="text-sm text-gray-600">The small percentage under each betting line shows what you're "paying for". The lower this number, the better the deal you're getting.</p>
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
                    <span class="font-medium">Pro Tip:</span> Compare odds across different bookmakers to find the best value for your bets.
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Legend -->
    <div class="border-t pt-3 mt-3">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><span class="font-medium">Spread:</span> Point handicap</div>
            <div><span class="font-medium">ML:</span> Moneyline (straight win)</div>
            <div><span class="font-medium">+150:</span> Profit $150 on $100 bet</div>
            <div><span class="font-medium">-150:</span> Bet $150 to profit $100</div>
        </div>
    </div>
</div>
