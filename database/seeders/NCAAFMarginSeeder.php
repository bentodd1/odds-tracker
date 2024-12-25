<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NCAAFMarginSeeder extends Seeder
{
    public function run()
    {
        $margins = [
            ['margin' => 1, 'occurrences' => 493, 'cumulative_percentage' => 3.36, 'is_key_number' => false],
            ['margin' => 2, 'occurrences' => 391, 'cumulative_percentage' => 6.02, 'is_key_number' => false],
            ['margin' => 3, 'occurrences' => 1350, 'cumulative_percentage' => 15.22, 'is_key_number' => true],
            ['margin' => 4, 'occurrences' => 512, 'cumulative_percentage' => 18.71, 'is_key_number' => false],
            ['margin' => 5, 'occurrences' => 369, 'cumulative_percentage' => 21.22, 'is_key_number' => false],
            ['margin' => 6, 'occurrences' => 434, 'cumulative_percentage' => 24.18, 'is_key_number' => false],
            ['margin' => 7, 'occurrences' => 1140, 'cumulative_percentage' => 31.95, 'is_key_number' => true],
            ['margin' => 8, 'occurrences' => 360, 'cumulative_percentage' => 34.40, 'is_key_number' => false],
            ['margin' => 9, 'occurrences' => 169, 'cumulative_percentage' => 35.55, 'is_key_number' => false],
            ['margin' => 10, 'occurrences' => 638, 'cumulative_percentage' => 39.90, 'is_key_number' => true],
            ['margin' => 11, 'occurrences' => 324, 'cumulative_percentage' => 42.11, 'is_key_number' => false],
            ['margin' => 12, 'occurrences' => 191, 'cumulative_percentage' => 43.41, 'is_key_number' => false],
            ['margin' => 13, 'occurrences' => 256, 'cumulative_percentage' => 45.15, 'is_key_number' => false],
            ['margin' => 14, 'occurrences' => 624, 'cumulative_percentage' => 49.40, 'is_key_number' => true],
            ['margin' => 15, 'occurrences' => 203, 'cumulative_percentage' => 50.79, 'is_key_number' => false],
            ['margin' => 16, 'occurrences' => 197, 'cumulative_percentage' => 52.13, 'is_key_number' => false],
            ['margin' => 17, 'occurrences' => 495, 'cumulative_percentage' => 55.50, 'is_key_number' => true],
            ['margin' => 18, 'occurrences' => 357, 'cumulative_percentage' => 57.93, 'is_key_number' => false],
            ['margin' => 19, 'occurrences' => 190, 'cumulative_percentage' => 59.23, 'is_key_number' => false],
            ['margin' => 20, 'occurrences' => 283, 'cumulative_percentage' => 61.16, 'is_key_number' => false],
            ['margin' => 21, 'occurrences' => 537, 'cumulative_percentage' => 64.82, 'is_key_number' => true],
            ['margin' => 22, 'occurrences' => 182, 'cumulative_percentage' => 66.06, 'is_key_number' => false],
            ['margin' => 23, 'occurrences' => 179, 'cumulative_percentage' => 67.28, 'is_key_number' => false],
            ['margin' => 24, 'occurrences' => 404, 'cumulative_percentage' => 70.03, 'is_key_number' => true],
            ['margin' => 25, 'occurrences' => 259, 'cumulative_percentage' => 71.79, 'is_key_number' => false],
            ['margin' => 26, 'occurrences' => 136, 'cumulative_percentage' => 72.72, 'is_key_number' => false],
            ['margin' => 27, 'occurrences' => 228, 'cumulative_percentage' => 74.27, 'is_key_number' => false],
            ['margin' => 28, 'occurrences' => 376, 'cumulative_percentage' => 76.83, 'is_key_number' => true],
            ['margin' => 29, 'occurrences' => 121, 'cumulative_percentage' => 77.66, 'is_key_number' => false],
            ['margin' => 30, 'occurrences' => 128, 'cumulative_percentage' => 78.53, 'is_key_number' => false],
            ['margin' => 31, 'occurrences' => 282, 'cumulative_percentage' => 80.45, 'is_key_number' => true],
            ['margin' => 32, 'occurrences' => 161, 'cumulative_percentage' => 81.55, 'is_key_number' => false],
            ['margin' => 33, 'occurrences' => 87, 'cumulative_percentage' => 82.14, 'is_key_number' => false],
            ['margin' => 34, 'occurrences' => 180, 'cumulative_percentage' => 83.37, 'is_key_number' => false],
            ['margin' => 35, 'occurrences' => 243, 'cumulative_percentage' => 85.02, 'is_key_number' => true],
            ['margin' => 36, 'occurrences' => 69, 'cumulative_percentage' => 85.49, 'is_key_number' => false],
            ['margin' => 37, 'occurrences' => 90, 'cumulative_percentage' => 86.11, 'is_key_number' => false],
            ['margin' => 38, 'occurrences' => 186, 'cumulative_percentage' => 87.37, 'is_key_number' => false],
            ['margin' => 39, 'occurrences' => 102, 'cumulative_percentage' => 88.07, 'is_key_number' => false],
            ['margin' => 40, 'occurrences' => 30, 'cumulative_percentage' => 88.27, 'is_key_number' => false],
            ['margin' => 41, 'occurrences' => 94, 'cumulative_percentage' => 88.91, 'is_key_number' => false],
            ['margin' => 42, 'occurrences' => 153, 'cumulative_percentage' => 89.96, 'is_key_number' => false],
            ['margin' => 43, 'occurrences' => 27, 'cumulative_percentage' => 90.14, 'is_key_number' => false],
            ['margin' => 44, 'occurrences' => 45, 'cumulative_percentage' => 90.45, 'is_key_number' => false],
            ['margin' => 45, 'occurrences' => 113, 'cumulative_percentage' => 91.22, 'is_key_number' => false],
            ['margin' => 46, 'occurrences' => 47, 'cumulative_percentage' => 91.54, 'is_key_number' => false],
            ['margin' => 47, 'occurrences' => 25, 'cumulative_percentage' => 91.71, 'is_key_number' => false],
            ['margin' => 48, 'occurrences' => 49, 'cumulative_percentage' => 92.04, 'is_key_number' => false],
            ['margin' => 49, 'occurrences' => 82, 'cumulative_percentage' => 92.60, 'is_key_number' => false],
            ['margin' => 50, 'occurrences' => 23, 'cumulative_percentage' => 92.76, 'is_key_number' => false],
            ['margin' => 51, 'occurrences' => 21, 'cumulative_percentage' => 92.90, 'is_key_number' => false],
            ['margin' => 52, 'occurrences' => 54, 'cumulative_percentage' => 93.27, 'is_key_number' => false],
            ['margin' => 53, 'occurrences' => 28, 'cumulative_percentage' => 93.46, 'is_key_number' => false],
            ['margin' => 54, 'occurrences' => 5, 'cumulative_percentage' => 93.49, 'is_key_number' => false],
            ['margin' => 55, 'occurrences' => 28, 'cumulative_percentage' => 93.68, 'is_key_number' => false],
            ['margin' => 56, 'occurrences' => 40, 'cumulative_percentage' => 93.96, 'is_key_number' => false],
            ['margin' => 57, 'occurrences' => 7, 'cumulative_percentage' => 94.00, 'is_key_number' => false],
            ['margin' => 58, 'occurrences' => 12, 'cumulative_percentage' => 94.09, 'is_key_number' => false],
            ['margin' => 59, 'occurrences' => 23, 'cumulative_percentage' => 94.24, 'is_key_number' => false],
            ['margin' => 60, 'occurrences' => 6, 'cumulative_percentage' => 94.28, 'is_key_number' => false],
            ['margin' => 61, 'occurrences' => 3, 'cumulative_percentage' => 94.30, 'is_key_number' => false],
            ['margin' => 62, 'occurrences' => 7, 'cumulative_percentage' => 94.35, 'is_key_number' => false],
            ['margin' => 63, 'occurrences' => 14, 'cumulative_percentage' => 94.45, 'is_key_number' => false],
            ['margin' => 64, 'occurrences' => 1, 'cumulative_percentage' => 94.45, 'is_key_number' => false],
            ['margin' => 65, 'occurrences' => 2, 'cumulative_percentage' => 94.47, 'is_key_number' => false],
            ['margin' => 66, 'occurrences' => 6, 'cumulative_percentage' => 94.51, 'is_key_number' => false],
            ['margin' => 67, 'occurrences' => 1, 'cumulative_percentage' => 94.52, 'is_key_number' => false],
            ['margin' => 68, 'occurrences' => 1, 'cumulative_percentage' => 94.52, 'is_key_number' => false],
            ['margin' => 69, 'occurrences' => 3, 'cumulative_percentage' => 94.54, 'is_key_number' => false],
            ['margin' => 70, 'occurrences' => 1, 'cumulative_percentage' => 94.55, 'is_key_number' => false],
            ['margin' => 71, 'occurrences' => 3, 'cumulative_percentage' => 94.57, 'is_key_number' => false],
            ['margin' => 72, 'occurrences' => 2, 'cumulative_percentage' => 94.58, 'is_key_number' => false],
            ['margin' => 73, 'occurrences' => 2, 'cumulative_percentage' => 94.60, 'is_key_number' => false],
            ['margin' => 74, 'occurrences' => 1, 'cumulative_percentage' => 94.60, 'is_key_number' => false],
            ['margin' => 78, 'occurrences' => 1, 'cumulative_percentage' => 94.61, 'is_key_number' => false],
        ];

        foreach ($margins as $margin) {
            DB::table('ncaaf_margins')->insert($margin);
        }
    }
}
