<?php

namespace App;

class WeatherProbabilityHelper
{
    /**
     * Extract temperature thresholds from a Kalshi market title.
     * Returns [type, threshold(s)]
     */
    public static function extractTemperaturesFromTitle(string $title): array
    {
        $lowTemp = null;
        $highTemp = null;
        $type = null;

        // Match ">90°" (above)
        if (preg_match('/be\s*>\s*(\d+)°/i', $title, $m)) {
            $type = 'above';
            $highTemp = (int)$m[1];
        }
        // Match "<83°" (below)
        elseif (preg_match('/be\s*<\s*(\d+)°/i', $title, $m)) {
            $type = 'below';
            $highTemp = (int)$m[1];
        }
        // Match "89-90°" (between)
        elseif (preg_match('/be\s*(\d+)-(\d+)°/i', $title, $m)) {
            $type = 'between';
            $lowTemp = (int)$m[1];
            $highTemp = (int)$m[2];
        }
        // Also support "less than" and "greater than" for legacy
        elseif (preg_match('/high temp.*?(?:be\s*)?(?:<|less than)\s*(\d+)/i', $title, $matches)) {
            $type = 'below';
            $highTemp = (int)$matches[1];
        } elseif (preg_match('/high temp.*?(?:be\s*)?(?:>|greater than)\s*(\d+)/i', $title, $matches)) {
            $type = 'above';
            $highTemp = (int)$matches[1];
        }

        return [
            'type' => $type,
            'low_temperature' => $lowTemp,
            'high_temperature' => $highTemp,
        ];
    }

    /**
     * Calculate the model probability for a market using the city's error distribution.
     * @param string $type 'less', 'greater', or 'between'
     * @param int|null $lowTemp
     * @param int|null $highTemp
     * @param int $accuPrediction
     * @param array $distribution Array of ['difference' => int, 'percentage' => float]
     * @return float Probability (0-1)
     */
    public static function calculateProbability(string $type, ?int $lowTemp, ?int $highTemp, int $accuPrediction, array $distribution): float
    {
        $prob = 0.0;
        if ($type === 'less' && $highTemp !== null) {
            $diff = $highTemp - $accuPrediction;
            foreach ($distribution as $item) {
                if ($item['difference'] <= $diff) {
                    $prob += $item['percentage'];
                }
            }
        } elseif ($type === 'greater' && $highTemp !== null) {
            $diff = $highTemp - $accuPrediction;
            foreach ($distribution as $item) {
                if ($item['difference'] >= $diff) {
                    $prob += $item['percentage'];
                }
            }
        } elseif ($type === 'between' && $lowTemp !== null && $highTemp !== null) {
            $diffLow = $lowTemp - $accuPrediction;
            $diffHigh = $highTemp - $accuPrediction;
            foreach ($distribution as $item) {
                if ($item['difference'] >= $diffLow && $item['difference'] <= $diffHigh) {
                    $prob += $item['percentage'];
                }
            }
        }
        return $prob / 100.0; // Convert percent to probability
    }
} 