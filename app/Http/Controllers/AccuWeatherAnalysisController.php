<?php

namespace App\Http\Controllers;

use App\Models\AccuWeatherPrediction;
use Illuminate\Http\Request;

class AccuWeatherAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $selectedCity = $request->input('city', 'all');
        $selectedMonth = $request->input('month', 'all');
        
        $query = AccuWeatherPrediction::whereNotNull('actual_high')
            ->whereRaw('prediction_date = DATE_SUB(target_date, INTERVAL 1 DAY)');
            
        if ($selectedCity !== 'all') {
            $query->where('city', $selectedCity);
        }
        
        if ($selectedMonth !== 'all') {
            $query->whereMonth('target_date', $selectedMonth);
        }
        
        $predictions = $query->get();
        
        // Get unique cities for the filter
        $cities = AccuWeatherPrediction::distinct()->pluck('city');
        
        // Process data for statistics
        $cityStats = [];
        $allDiffs = [];
        
        foreach ($predictions as $prediction) {
            // Flip the sign as requested
            $flippedHighDiff = -$prediction->high_difference;
            
            if (!isset($cityStats[$prediction->city])) {
                $cityStats[$prediction->city] = [
                    'diffs' => [],
                    'count' => 0
                ];
            }
            
            $cityStats[$prediction->city]['diffs'][] = $flippedHighDiff;
            $cityStats[$prediction->city]['count']++;
            $allDiffs[] = $flippedHighDiff;
        }
        
        // Calculate statistics for each city
        foreach ($cityStats as $city => &$stats) {
            sort($stats['diffs']);
            $mid = floor(count($stats['diffs']) / 2);
            $stats['median'] = count($stats['diffs']) % 2 === 0 
                ? ($stats['diffs'][$mid - 1] + $stats['diffs'][$mid]) / 2
                : $stats['diffs'][$mid];
                
            // Calculate distribution
            $distribution = [];
            foreach ($stats['diffs'] as $diff) {
                $distribution[$diff] = ($distribution[$diff] ?? 0) + 1;
            }
            
            $stats['distribution'] = collect($distribution)
                ->map(function ($count, $diff) use ($stats) {
                    return [
                        'difference' => (float)$diff,
                        'count' => $count,
                        'percentage' => round(($count / $stats['count']) * 100, 1)
                    ];
                })
                ->sortBy('difference')
                ->values();
        }
        
        // Calculate overall statistics
        sort($allDiffs);
        $midAll = floor(count($allDiffs) / 2);
        $overallMedian = count($allDiffs) % 2 === 0 
            ? ($allDiffs[$midAll - 1] + $allDiffs[$midAll]) / 2
            : $allDiffs[$midAll];
            
        $overallDistribution = [];
        foreach ($allDiffs as $diff) {
            $overallDistribution[$diff] = ($overallDistribution[$diff] ?? 0) + 1;
        }
        
        $overallStats = [
            'count' => count($allDiffs),
            'median' => $overallMedian,
            'distribution' => collect($overallDistribution)
                ->map(function ($count, $diff) use ($allDiffs) {
                    return [
                        'difference' => (float)$diff,
                        'count' => $count,
                        'percentage' => round(($count / count($allDiffs)) * 100, 1)
                    ];
                })
                ->sortBy('difference')
                ->values()
        ];
        
        return view('accuweather.analysis', compact(
            'cityStats',
            'overallStats',
            'cities',
            'selectedCity',
            'selectedMonth'
        ));
    }
} 