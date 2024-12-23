<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NFLMarginSeeder extends Seeder
{
    public function run()
    {
        $margins = [
            ['margin' => 0, 'occurrences' => 14, 'cumulative_percentage' => 0.22, 'is_key_number' => true],
            ['margin' => 1, 'occurrences' => 263, 'cumulative_percentage' => 4.32, 'is_key_number' => false],
            ['margin' => 2, 'occurrences' => 257, 'cumulative_percentage' => 8.33, 'is_key_number' => false],
            ['margin' => 3, 'occurrences' => 962, 'cumulative_percentage' => 23.34, 'is_key_number' => true],
            ['margin' => 4, 'occurrences' => 313, 'cumulative_percentage' => 28.23, 'is_key_number' => false],
            ['margin' => 5, 'occurrences' => 231, 'cumulative_percentage' => 31.83, 'is_key_number' => false],
            ['margin' => 6, 'occurrences' => 388, 'cumulative_percentage' => 37.88, 'is_key_number' => false],
            ['margin' => 7, 'occurrences' => 582, 'cumulative_percentage' => 46.97, 'is_key_number' => true],
            ['margin' => 8, 'occurrences' => 244, 'cumulative_percentage' => 50.77, 'is_key_number' => false],
            ['margin' => 9, 'occurrences' => 104, 'cumulative_percentage' => 52.40, 'is_key_number' => false],
            ['margin' => 10, 'occurrences' => 362, 'cumulative_percentage' => 58.04, 'is_key_number' => true],
            ['margin' => 11, 'occurrences' => 152, 'cumulative_percentage' => 60.42, 'is_key_number' => false],
            ['margin' => 12, 'occurrences' => 103, 'cumulative_percentage' => 62.02, 'is_key_number' => false],
            ['margin' => 13, 'occurrences' => 177, 'cumulative_percentage' => 64.78, 'is_key_number' => false],
            ['margin' => 14, 'occurrences' => 311, 'cumulative_percentage' => 69.64, 'is_key_number' => true],
            ['margin' => 15, 'occurrences' => 98, 'cumulative_percentage' => 71.17, 'is_key_number' => false],
            ['margin' => 16, 'occurrences' => 136, 'cumulative_percentage' => 73.29, 'is_key_number' => false],
            ['margin' => 17, 'occurrences' => 211, 'cumulative_percentage' => 76.58, 'is_key_number' => true],
            ['margin' => 18, 'occurrences' => 150, 'cumulative_percentage' => 78.92, 'is_key_number' => false],
            ['margin' => 19, 'occurrences' => 73, 'cumulative_percentage' => 80.06, 'is_key_number' => false],
            ['margin' => 20, 'occurrences' => 141, 'cumulative_percentage' => 82.26, 'is_key_number' => false],
            ['margin' => 21, 'occurrences' => 180, 'cumulative_percentage' => 85.07, 'is_key_number' => true],
            ['margin' => 22, 'occurrences' => 61, 'cumulative_percentage' => 86.02, 'is_key_number' => false],
            ['margin' => 23, 'occurrences' => 71, 'cumulative_percentage' => 87.13, 'is_key_number' => false],
            ['margin' => 24, 'occurrences' => 139, 'cumulative_percentage' => 89.30, 'is_key_number' => true],
            ['margin' => 25, 'occurrences' => 70, 'cumulative_percentage' => 90.39, 'is_key_number' => false],
            ['margin' => 26, 'occurrences' => 51, 'cumulative_percentage' => 91.18, 'is_key_number' => false],
            ['margin' => 27, 'occurrences' => 82, 'cumulative_percentage' => 92.46, 'is_key_number' => false],
            ['margin' => 28, 'occurrences' => 107, 'cumulative_percentage' => 94.13, 'is_key_number' => true],
            ['margin' => 29, 'occurrences' => 35, 'cumulative_percentage' => 94.68, 'is_key_number' => false],
            ['margin' => 30, 'occurrences' => 31, 'cumulative_percentage' => 95.16, 'is_key_number' => false],
            ['margin' => 31, 'occurrences' => 71, 'cumulative_percentage' => 96.27, 'is_key_number' => true],
            ['margin' => 32, 'occurrences' => 32, 'cumulative_percentage' => 96.77, 'is_key_number' => false],
            ['margin' => 33, 'occurrences' => 18, 'cumulative_percentage' => 97.05, 'is_key_number' => false],
            ['margin' => 34, 'occurrences' => 33, 'cumulative_percentage' => 97.57, 'is_key_number' => false],
            ['margin' => 35, 'occurrences' => 40, 'cumulative_percentage' => 98.19, 'is_key_number' => true],
            ['margin' => 36, 'occurrences' => 5, 'cumulative_percentage' => 98.27, 'is_key_number' => false],
            ['margin' => 37, 'occurrences' => 23, 'cumulative_percentage' => 98.63, 'is_key_number' => false],
            ['margin' => 38, 'occurrences' => 30, 'cumulative_percentage' => 99.10, 'is_key_number' => false],
            ['margin' => 39, 'occurrences' => 4, 'cumulative_percentage' => 99.16, 'is_key_number' => false],
            ['margin' => 40, 'occurrences' => 10, 'cumulative_percentage' => 99.31, 'is_key_number' => false],
            ['margin' => 41, 'occurrences' => 11, 'cumulative_percentage' => 99.49, 'is_key_number' => false],
            ['margin' => 42, 'occurrences' => 7, 'cumulative_percentage' => 99.59, 'is_key_number' => false],
            ['margin' => 43, 'occurrences' => 4, 'cumulative_percentage' => 99.66, 'is_key_number' => false],
            ['margin' => 44, 'occurrences' => 2, 'cumulative_percentage' => 99.69, 'is_key_number' => false],
            ['margin' => 45, 'occurrences' => 7, 'cumulative_percentage' => 99.80, 'is_key_number' => false],
            ['margin' => 46, 'occurrences' => 3, 'cumulative_percentage' => 99.84, 'is_key_number' => false],
            ['margin' => 48, 'occurrences' => 2, 'cumulative_percentage' => 99.88, 'is_key_number' => false],
            ['margin' => 49, 'occurrences' => 3, 'cumulative_percentage' => 99.92, 'is_key_number' => false],
            ['margin' => 50, 'occurrences' => 1, 'cumulative_percentage' => 99.94, 'is_key_number' => false],
            ['margin' => 52, 'occurrences' => 1, 'cumulative_percentage' => 99.95, 'is_key_number' => false],
            ['margin' => 55, 'occurrences' => 1, 'cumulative_percentage' => 99.97, 'is_key_number' => false],
            ['margin' => 58, 'occurrences' => 1, 'cumulative_percentage' => 99.98, 'is_key_number' => false],
            ['margin' => 59, 'occurrences' => 1, 'cumulative_percentage' => 100.00, 'is_key_number' => false],
        ];

        foreach ($margins as $margin) {
            DB::table('nfl_margins')->insert($margin);
        }
    }
}
