<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MLBMarginSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $margins = [
            ['margin' => 1, 'occurrences' => 258, 'cumulative_percentage' => 27.13, 'is_key_number' => true],
            ['margin' => 2, 'occurrences' => 161, 'cumulative_percentage' => 44.06, 'is_key_number' => true],
            ['margin' => 3, 'occurrences' => 138, 'cumulative_percentage' => 58.57, 'is_key_number' => true],
            ['margin' => 4, 'occurrences' => 118, 'cumulative_percentage' => 70.98, 'is_key_number' => true],
            ['margin' => 5, 'occurrences' => 92, 'cumulative_percentage' => 80.65, 'is_key_number' => false],
            ['margin' => 6, 'occurrences' => 56, 'cumulative_percentage' => 86.54, 'is_key_number' => false],
            ['margin' => 7, 'occurrences' => 36, 'cumulative_percentage' => 90.33, 'is_key_number' => false],
            ['margin' => 8, 'occurrences' => 22, 'cumulative_percentage' => 92.64, 'is_key_number' => false],
            ['margin' => 9, 'occurrences' => 24, 'cumulative_percentage' => 95.16, 'is_key_number' => false],
            ['margin' => 10, 'occurrences' => 10, 'cumulative_percentage' => 96.21, 'is_key_number' => false],
            ['margin' => 11, 'occurrences' => 11, 'cumulative_percentage' => 97.37, 'is_key_number' => false],
            ['margin' => 12, 'occurrences' => 9, 'cumulative_percentage' => 98.32, 'is_key_number' => false],
            ['margin' => 13, 'occurrences' => 7, 'cumulative_percentage' => 99.05, 'is_key_number' => false],
            ['margin' => 14, 'occurrences' => 3, 'cumulative_percentage' => 99.37, 'is_key_number' => false],
            ['margin' => 15, 'occurrences' => 2, 'cumulative_percentage' => 99.58, 'is_key_number' => false],
            ['margin' => 17, 'occurrences' => 1, 'cumulative_percentage' => 99.68, 'is_key_number' => false],
            ['margin' => 18, 'occurrences' => 1, 'cumulative_percentage' => 99.79, 'is_key_number' => false],
            ['margin' => 19, 'occurrences' => 1, 'cumulative_percentage' => 99.89, 'is_key_number' => false],
            ['margin' => 20, 'occurrences' => 1, 'cumulative_percentage' => 100.00, 'is_key_number' => false],
        ];

        foreach ($margins as $margin) {
            DB::table('mlb_margins')->insert($margin);
        }
    }
}
