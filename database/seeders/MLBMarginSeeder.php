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
            ['margin' => 1, 'occurrences' => 13053, 'cumulative_percentage' => 28.00, 'is_key_number' => true],
            ['margin' => 2, 'occurrences' => 8019, 'cumulative_percentage' => 45.20, 'is_key_number' => true],
            ['margin' => 3, 'occurrences' => 6436, 'cumulative_percentage' => 59.00, 'is_key_number' => true],
            ['margin' => 4, 'occurrences' => 5324, 'cumulative_percentage' => 70.42, 'is_key_number' => true],
            ['margin' => 5, 'occurrences' => 3912, 'cumulative_percentage' => 78.81, 'is_key_number' => false],
            ['margin' => 6, 'occurrences' => 2942, 'cumulative_percentage' => 85.12, 'is_key_number' => false],
            ['margin' => 7, 'occurrences' => 2076, 'cumulative_percentage' => 89.57, 'is_key_number' => false],
            ['margin' => 8, 'occurrences' => 1488, 'cumulative_percentage' => 92.76, 'is_key_number' => false],
            ['margin' => 9, 'occurrences' => 1129, 'cumulative_percentage' => 95.18, 'is_key_number' => false],
            ['margin' => 10, 'occurrences' => 758, 'cumulative_percentage' => 96.81, 'is_key_number' => false],
            ['margin' => 11, 'occurrences' => 512, 'cumulative_percentage' => 97.91, 'is_key_number' => false],
            ['margin' => 12, 'occurrences' => 343, 'cumulative_percentage' => 98.64, 'is_key_number' => false],
            ['margin' => 13, 'occurrences' => 241, 'cumulative_percentage' => 99.16, 'is_key_number' => false],
            ['margin' => 14, 'occurrences' => 153, 'cumulative_percentage' => 99.49, 'is_key_number' => false],
            ['margin' => 15, 'occurrences' => 101, 'cumulative_percentage' => 99.71, 'is_key_number' => false],
            ['margin' => 16, 'occurrences' => 74, 'cumulative_percentage' => 99.87, 'is_key_number' => false],
            ['margin' => 17, 'occurrences' => 62, 'cumulative_percentage' => 100.00, 'is_key_number' => false]
        ];

        foreach ($margins as $margin) {
            DB::table('mlb_margins')->insert($margin);
        }
    }
}
