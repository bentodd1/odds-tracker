<?php

namespace App\Http\Controllers;

use App\Models\AccuWeatherPrediction;
use App\Models\NwsWeatherPrediction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\WeatherProbabilityHelper;
use Illuminate\Support\Facades\Log;

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
        $selectedHour = $request->input('hour', 1);

        // Load error distributions for all cities (reuse logic from AccuWeatherAnalysisController)
        $cityDistributions = [];
        $predictions = AccuWeatherPrediction::whereNotNull('actual_high')
            ->whereRaw('prediction_date = DATE_SUB(target_date, INTERVAL 1 DAY)')
            ->when($selectedHour !== 'all', function($query) use ($selectedHour) {
                $query->whereRaw('HOUR(prediction_time) = ?', [$selectedHour]);
            })
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
                ->when($selectedHour !== 'all', function($query) use ($selectedHour) {
                    $query->whereRaw('HOUR(prediction_time) = ?', [$selectedHour]);
                })
                ->orderBy('prediction_time', 'desc')
                ->first();

            $nws = NwsWeatherPrediction::where('city', $city)
                ->where('target_date', $selectedDate)
                ->when($selectedHour !== 'all', function($query) use ($selectedHour) {
                    $query->whereRaw('HOUR(prediction_time) = ?', [$selectedHour]);
                })
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
            'selectedHour' => $selectedHour,
        ]);
    }

    public function nwsIndex(Request $request)
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
        $selectedHour = $request->input('hour', 1);

        // Load error distributions for all cities (reuse logic from AccuWeatherAnalysisController)
        $cityDistributions = [];
        $predictions = NwsWeatherPrediction::whereNotNull('actual_high')
            ->whereRaw('prediction_date = DATE_SUB(target_date, INTERVAL 1 DAY)')
            ->when($selectedHour !== 'all', function($query) use ($selectedHour) {
                $query->whereRaw('HOUR(prediction_time) = ?', [$selectedHour]);
            })
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
            $nws = NwsWeatherPrediction::where('city', $city)
                ->where('target_date', $selectedDate)
                ->when($selectedHour !== 'all', function($query) use ($selectedHour) {
                    $query->whereRaw('HOUR(prediction_time) = ?', [$selectedHour]);
                })
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
            if ($nws && $kalshiMarkets->count() && isset($cityDistributions[$city])) {
                foreach ($kalshiMarkets as $market) {
                    $parsed = WeatherProbabilityHelper::extractTemperaturesFromTitle($market->title);
                    $type = $parsed['type'];
                    $lowTemp = $parsed['low_temperature'];
                    $highTemp = $parsed['high_temperature'];
                    $nwsHigh = $nws->predicted_high;
                    $distribution = $cityDistributions[$city];
                    $modelProb = WeatherProbabilityHelper::calculateProbability($type, $lowTemp, $highTemp, $nwsHigh, $distribution);
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
                'nws' => $nws,
                'kalshi_markets' => $kalshiMarkets,
                'hours_to_3pm' => $hoursTo3pm,
                'timezone' => $cityTimeZone,
                'best_bet' => $bestBet,
            ];
        }

        return view('dashboard.nws-weather', [
            'results' => $results,
            'today' => $today,
            'tomorrow' => $tomorrow,
            'selectedDate' => $selectedDate,
            'cityDistributions' => $cityDistributions,
            'selectedHour' => $selectedHour,
        ]);
    }

    public function combinedIndex(Request $request)
    {
        $cities = [
            'Austin', 'Denver', 'Chicago', 'Los Angeles', 'New York', 'Philadelphia', 'Miami'
        ];
        $results = [];
        $now = Carbon::now();
        $today = $now->copy()->setTimezone('America/Chicago')->toDateString();
        $tomorrow = $now->copy()->setTimezone('America/Chicago')->addDay()->toDateString();
        $selectedDate = $request->input('date', $today);
        $selectedHour = $request->input('hour', 1);
        $isToday = $selectedDate === $today;

        // AccuWeather distributions
        $cityDistributionsAccu = [];
        $accuPredictions = AccuWeatherPrediction::whereNotNull('actual_high')
            ->whereRaw('prediction_date = DATE_SUB(target_date, INTERVAL 1 DAY)')
            ->when($selectedHour !== 'all', function($query) use ($selectedHour) {
                $query->whereRaw('HOUR(prediction_time) = ?', [$selectedHour]);
            })
            ->get();
        foreach ($cities as $city) {
            $cityDiffs = $accuPredictions->where('city', $city)->map(function($p) { return -$p->high_difference; });
            $distribution = collect($cityDiffs)->countBy()->map(function($count, $diff) use ($cityDiffs) {
                return [
                    'difference' => (int)$diff,
                    'percentage' => round(($count / max(1, count($cityDiffs))) * 100, 1)
                ];
            })->sortBy('difference')->values()->all();
            $cityDistributionsAccu[$city] = $distribution;
        }

        // NWS distributions
        $cityDistributionsNws = [];
        $nwsPredictions = NwsWeatherPrediction::whereNotNull('actual_high')
            ->whereRaw('prediction_date = DATE_SUB(target_date, INTERVAL 1 DAY)')
            ->when($selectedHour !== 'all', function($query) use ($selectedHour) {
                $query->whereRaw('HOUR(prediction_time) = ?', [$selectedHour]);
            })
            ->get();
        foreach ($cities as $city) {
            $cityDiffs = $nwsPredictions->where('city', $city)->map(function($p) { return -$p->high_difference; });
            $distribution = collect($cityDiffs)->countBy()->map(function($count, $diff) use ($cityDiffs) {
                return [
                    'difference' => (int)$diff,
                    'percentage' => round(($count / max(1, count($cityDiffs))) * 100, 1)
                ];
            })->sortBy('difference')->values()->all();
            $cityDistributionsNws[$city] = $distribution;
        }

        foreach ($cities as $city) {
            $accuweather = AccuWeatherPrediction::where('city', $city)
                ->where('target_date', $selectedDate)
                ->when($isToday, function($query) use ($today) {
                    $query->whereDate('prediction_date', $today);
                })
                ->when($selectedHour !== 'all', function($query) use ($selectedHour) {
                    $query->whereRaw('HOUR(prediction_time) = ?', [$selectedHour]);
                })
                ->orderBy('prediction_time', 'desc')
                ->first();

            $nws = NwsWeatherPrediction::where('city', $city)
                ->where('target_date', $selectedDate)
                ->when($isToday, function($query) use ($today) {
                    $query->whereDate('prediction_date', $today);
                })
                ->when($selectedHour !== 'all', function($query) use ($selectedHour) {
                    $query->whereRaw('HOUR(prediction_time) = ?', [$selectedHour]);
                })
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

            foreach ($kalshiMarkets as $market) {
                $parsed = WeatherProbabilityHelper::extractTemperaturesFromTitle($market->title);
                $type = $parsed['type'];
                $lowTemp = $parsed['low_temperature'];
                $highTemp = $parsed['high_temperature'];

                // AccuWeather model
                $accuHigh = $accuweather ? $accuweather->predicted_high : null;
                $accuDist = $cityDistributionsAccu[$city] ?? [];
                $accuProb = ($accuHigh !== null && $accuDist) ? WeatherProbabilityHelper::calculateProbability($type, $lowTemp, $highTemp, $accuHigh, $accuDist) : null;

                // NWS model
                $nwsHigh = $nws ? $nws->predicted_high : null;
                $nwsDist = $cityDistributionsNws[$city] ?? [];
                $nwsProb = ($nwsHigh !== null && $nwsDist) ? WeatherProbabilityHelper::calculateProbability($type, $lowTemp, $highTemp, $nwsHigh, $nwsDist) : null;

                $market->accu_model_prob = $accuProb;
                $market->nws_model_prob = $nwsProb;
            }

            $results[] = [
                'city' => $city,
                'accuweather' => $accuweather,
                'nws' => $nws,
                'kalshi_markets' => $kalshiMarkets,
            ];
        }

        return view('dashboard.combined-weather', [
            'results' => $results,
            'today' => $today,
            'tomorrow' => $tomorrow,
            'selectedDate' => $selectedDate,
            'selectedHour' => $selectedHour,
        ]);
    }
}
