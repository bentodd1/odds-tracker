<?php

namespace App\Http\Controllers;

use App\Models\AccuWeatherPrediction;
use App\Models\NwsWeatherPrediction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\WeatherProbabilityHelper;

class WeatherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $cities = [
            'Austin', 'Denver', 'Chicago', 'Los Angeles', 'New York', 'Philadelphia', 'Miami'
        ];
        $results = [];
        $now = Carbon::now();
        $today = $now->copy()->setTimezone('America/Chicago')->toDateString();
        $tomorrow = $now->copy()->setTimezone('America/Chicago')->addDay()->toDateString();
        $targetDates = [$today, $tomorrow];
        $selectedDate = $request->input('date', $today);

        // Load error distributions for all cities (reuse logic from AccuWeatherAnalysisController)
        $cityDistributions = [];
        $predictions = AccuWeatherPrediction::whereNotNull('actual_high')
            ->whereRaw('prediction_date = DATE_SUB(target_date, INTERVAL 1 DAY)')
            ->get();
        foreach ($cities as $city) {
            $cityDiffs = $predictions->where('city', $city)->map(function($p) { return -$p->high_difference; });
            $distribution = collect($cityDiffs)->countBy()->map(function($count, $diff) use ($cityDiffs) {
                return [
                    'difference' => (int)$diff,
                    'percentage' => round(($count / max(1, count($cityDiffs))) * 100, 1)
                ];
            })->sortBy('difference')->values()->all();
            $cityDistributions[$city] = $distribution;
        }

        foreach ($cities as $city) {
            $accuweather = AccuWeatherPrediction::where('city', $city)
                ->where('target_date', $selectedDate)
                ->orderBy('prediction_time', 'desc')
                ->first();

            $nws = NwsWeatherPrediction::where('city', $city)
                ->where('target_date', $selectedDate)
                ->orderBy('prediction_time', 'desc')
                ->first();

            $kalshiMarkets = \App\Models\KalshiWeatherMarket::where('location', $city)
                ->whereDate('target_date', $selectedDate)
                ->with(['states' => function($query) use ($selectedDate) {
                    $closeDate = Carbon::parse($selectedDate)->addDay()->toDateString();
                    $query->whereDate('close_time', $closeDate)
                          ->orderByDesc('collected_at');
                }])
                ->get()
                ->map(function($market) {
                    $market->filtered_state = $market->states->first();
                    return $market;
                });

            $cityTimeZone = match($city) {
                'Austin' => 'America/Chicago',
                'Denver' => 'America/Denver',
                'Chicago' => 'America/Chicago',
                'Los Angeles' => 'America/Los_Angeles',
                'New York' => 'America/New_York',
                'Philadelphia' => 'America/New_York',
                'Miami' => 'America/New_York',
                default => 'America/Chicago',
            };
            $nowCity = $now->copy()->setTimezone($cityTimeZone);
            $threePm = $nowCity->copy()->setTime(15, 0, 0);
            $hoursTo3pm = $nowCity->floatDiffInHours($threePm, false); // Use float for more precision

            $bestBet = null;
            $bestEdge = -999;
            if ($accuweather && $kalshiMarkets->count() && isset($cityDistributions[$city])) {
                foreach ($kalshiMarkets as $market) {
                    $parsed = WeatherProbabilityHelper::extractTemperaturesFromTitle($market->title);
                    $type = $parsed['type'];
                    $lowTemp = $parsed['low_temperature'];
                    $highTemp = $parsed['high_temperature'];
                    $accuHigh = $accuweather->predicted_high;
                    $distribution = $cityDistributions[$city];
                    $modelProb = WeatherProbabilityHelper::calculateProbability($type, $lowTemp, $highTemp, $accuHigh, $distribution);
                    $marketProb = null;
                    if ($market->filtered_state && $market->filtered_state->yes_ask !== null) {
                        $marketProb = $market->filtered_state->yes_ask / 100.0;
                    }
                    $edge = $marketProb !== null ? $modelProb - $marketProb : -999;
                    if ($edge > $bestEdge) {
                        $bestEdge = $edge;
                        $bestBet = [
                            'market_id' => $market->id,
                            'edge' => $edge,
                            'model_prob' => $modelProb,
                            'market_prob' => $marketProb,
                        ];
                    }
                }
            }

            $results[] = [
                'city' => $city,
                'accuweather' => $accuweather,
                'nws' => $nws,
                'kalshi_markets' => $kalshiMarkets,
                'hours_to_3pm' => $hoursTo3pm,
                'timezone' => $cityTimeZone,
                'best_bet' => $bestBet,
            ];
        }

        return view('dashboard.weather', [
            'results' => $results,
            'today' => $today,
            'tomorrow' => $tomorrow,
            'selectedDate' => $selectedDate,
            'cityDistributions' => $cityDistributions,
        ]);
    }
}
