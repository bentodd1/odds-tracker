<?php

namespace App\Http\Controllers;

use App\Models\NwsWeatherPrediction;
use Illuminate\Http\Request;

class NwsWeatherAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $selectedCity = $request->input('city', 'all');
        $selectedMonth = $request->input('month', 'all');
        $selectedHour = $request->input('hour', 'all');
        
        $query = NwsWeatherPrediction::whereNotNull('actual_high')
            ->whereRaw('prediction_date = DATE_SUB(target_date, INTERVAL 1 DAY)');
            
        if ($selectedCity !== 'all') {
            $query->where('city', $selectedCity);
        }
        
        if ($selectedMonth !== 'all') {
            $query->whereMonth('target_date', $selectedMonth);
        }

        if ($selectedHour !== 'all') {
            $query->where('forecast_hour', $selectedHour);
        }
        
        $predictions = $query->get();
        
        // Get unique cities for the filter
        $cities = NwsWeatherPrediction::distinct()->pluck('city');
        
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
            $stats['median'] = $this->calculateMedian($stats['diffs']);
            $stats['distribution'] = $this->calculateDistribution($stats['diffs']);
        }
        
        // Calculate overall statistics
        sort($allDiffs);
        $overallMedian = $this->calculateMedian($allDiffs);
        $overallDistribution = $this->calculateDistribution($allDiffs);
        
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
        
        return view('nws.analysis', compact(
            'cityStats',
            'overallStats',
            'cities',
            'selectedCity',
            'selectedMonth',
            'selectedHour'
        ));
    }

    private function calculateMedian(array $numbers): float
    {
        $count = count($numbers);
        if ($count === 0) {
            return 0;
        }
        
        $middleIndex = floor($count / 2);
        if ($count % 2 === 0) {
            return ($numbers[$middleIndex - 1] + $numbers[$middleIndex]) / 2;
        }
        
        return $numbers[$middleIndex];
    }

    private function calculateDistribution(array $numbers): array
    {
        $distribution = [];
        foreach ($numbers as $num) {
            $rounded = round($num);
            if (!isset($distribution[$rounded])) {
                $distribution[$rounded] = 0;
            }
            $distribution[$rounded]++;
        }
        return $distribution;
    }
} 