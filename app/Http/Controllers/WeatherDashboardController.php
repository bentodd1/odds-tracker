<?php

namespace App\Http\Controllers;

use App\Models\AccuWeatherPrediction;
use App\Models\NwsWeatherPrediction;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        foreach ($cities as $city) {
            $accuweather = AccuWeatherPrediction::where('city', $city)
                ->whereIn('target_date', $targetDates)
                ->orderBy('prediction_time', 'desc')
                ->get()
                ->keyBy('target_date');

            $nws = NwsWeatherPrediction::where('city', $city)
                ->whereIn('target_date', $targetDates)
                ->orderBy('prediction_time', 'desc')
                ->get()
                ->keyBy('target_date');

            // Kalshi markets for this city and selected date
            $kalshiMarkets = \App\Models\KalshiWeatherMarket::whereHas('event', function($q) use ($city, $selectedDate) {
                $q->where('location', $city)
                  ->whereDate('target_date', $selectedDate);
            })
            ->with(['states' => function($query) use ($selectedDate) {
                $closeDate = Carbon::parse($selectedDate)->addDay()->toDateString();
                $query->whereDate('close_time', $closeDate)
                      ->orderByDesc('collected_at');
            }])
            ->get()
            ->map(function($market) {
                // Only keep the most recent state for the market
                $market->filtered_state = $market->states->first();
                return $market;
            });

            // Calculate hours until 3PM local time for each city
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
            $hoursTo3pm = $nowCity->diffInHours($threePm, false); // negative if past 3pm

            $results[] = [
                'city' => $city,
                'accuweather' => $accuweather,
                'nws' => $nws,
                'kalshi_markets' => $kalshiMarkets,
                'hours_to_3pm' => $hoursTo3pm,
                'timezone' => $cityTimeZone,
            ];
        }

        return view('dashboard.weather', [
            'results' => $results,
            'today' => $today,
            'tomorrow' => $tomorrow,
            'selectedDate' => $selectedDate,
        ]);
    }
} 