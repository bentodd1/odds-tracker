<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NCAABMarginSeeder extends Seeder
{
    public function run()
    {
        $margins = [
            ['margin' => 1, 'occurrences' => 1982, 'cumulative_percentage' => 3.29, 'is_key_number' => false],
            ['margin' => 2, 'occurrences' => 2138, 'cumulative_percentage' => 9.14, 'is_key_number' => true],
            ['margin' => 3, 'occurrences' => 1606, 'cumulative_percentage' => 17.40, 'is_key_number' => true],
            ['margin' => 4, 'occurrences' => 1615, 'cumulative_percentage' => 26.97, 'is_key_number' => false],
            ['margin' => 5, 'occurrences' => 1572, 'cumulative_percentage' => 36.59, 'is_key_number' => false],
            ['margin' => 6, 'occurrences' => 1462, 'cumulative_percentage' => 45.95, 'is_key_number' => false],
            ['margin' => 7, 'occurrences' => 1311, 'cumulative_percentage' => 54.66, 'is_key_number' => true],
            ['margin' => 8, 'occurrences' => 1172, 'cumulative_percentage' => 62.47, 'is_key_number' => false],
            ['margin' => 9, 'occurrences' => 983, 'cumulative_percentage' => 69.46, 'is_key_number' => false],
            ['margin' => 10, 'occurrences' => 849, 'cumulative_percentage' => 75.31, 'is_key_number' => true],
            ['margin' => 11, 'occurrences' => 748, 'cumulative_percentage' => 80.37, 'is_key_number' => false],
            ['margin' => 12, 'occurrences' => 603, 'cumulative_percentage' => 84.83, 'is_key_number' => false],
            ['margin' => 13, 'occurrences' => 456, 'cumulative_percentage' => 88.42, 'is_key_number' => false],
            ['margin' => 14, 'occurrences' => 386, 'cumulative_percentage' => 91.14, 'is_key_number' => true],
            ['margin' => 15, 'occurrences' => 273, 'cumulative_percentage' => 93.44, 'is_key_number' => false],
            ['margin' => 16, 'occurrences' => 211, 'cumulative_percentage' => 95.06, 'is_key_number' => false],
            ['margin' => 17, 'occurrences' => 167, 'cumulative_percentage' => 96.32, 'is_key_number' => true],
            ['margin' => 18, 'occurrences' => 114, 'cumulative_percentage' => 97.31, 'is_key_number' => false],
            ['margin' => 19, 'occurrences' => 104, 'cumulative_percentage' => 98.61, 'is_key_number' => false],
            ['margin' => 20, 'occurrences' => 67, 'cumulative_percentage' => 99.01, 'is_key_number' => false],
            ['margin' => 21, 'occurrences' => 55, 'cumulative_percentage' => 99.34, 'is_key_number' => true],
            ['margin' => 22, 'occurrences' => 30, 'cumulative_percentage' => 99.52, 'is_key_number' => false],
            ['margin' => 23, 'occurrences' => 20, 'cumulative_percentage' => 99.62, 'is_key_number' => false],
            ['margin' => 24, 'occurrences' => 19, 'cumulative_percentage' => 99.75, 'is_key_number' => true],
            ['margin' => 25, 'occurrences' => 7, 'cumulative_percentage' => 99.79, 'is_key_number' => false],
            ['margin' => 26, 'occurrences' => 6, 'cumulative_percentage' => 99.83, 'is_key_number' => false],
            ['margin' => 27, 'occurrences' => 11, 'cumulative_percentage' => 99.89, 'is_key_number' => false],
            ['margin' => 28, 'occurrences' => 7, 'cumulative_percentage' => 99.93, 'is_key_number' => true],
            ['margin' => 29, 'occurrences' => 4, 'cumulative_percentage' => 99.96, 'is_key_number' => false],
            ['margin' => 30, 'occurrences' => 1, 'cumulative_percentage' => 99.96, 'is_key_number' => false],
            ['margin' => 31, 'occurrences' => 2, 'cumulative_percentage' => 99.98, 'is_key_number' => true],
            ['margin' => 32, 'occurrences' => 2, 'cumulative_percentage' => 99.99, 'is_key_number' => false],
            ['margin' => 34, 'occurrences' => 1, 'cumulative_percentage' => 99.99, 'is_key_number' => false],
            ['margin' => 35, 'occurrences' => 1, 'cumulative_percentage' => 100.00, 'is_key_number' => true]
        ];

        foreach ($margins as $margin) {
            DB::table('ncaab_margins')->insert($margin);
        }
    }
}
