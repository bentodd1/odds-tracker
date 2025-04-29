<?php

namespace App\Http\Controllers;

use App\Models\AccuWeatherPrediction;
use Illuminate\Http\Request;

class WeatherPredictionController extends Controller
{
    public function index()
    {
        $predictions = AccuWeatherPrediction::orderBy('target_date', 'desc')
            ->orderBy('city')
            ->paginate(50);

        return view('weather-predictions.index', compact('predictions'));
    }

    public function show($city)
    {
        $predictions = AccuWeatherPrediction::where('city', $city)
            ->orderBy('target_date', 'desc')
            ->paginate(50);

        return view('weather-predictions.show', compact('predictions', 'city'));
    }

    public function stats()
    {
        $stats = AccuWeatherPrediction::whereNotNull('actual_high')
            ->selectRaw('
                city,
                AVG(ABS(high_difference)) as avg_high_diff,
                AVG(ABS(low_difference)) as avg_low_diff,
                COUNT(*) as total_predictions
            ')
            ->groupBy('city')
            ->get();

        return view('weather-predictions.stats', compact('stats'));
    }
} 