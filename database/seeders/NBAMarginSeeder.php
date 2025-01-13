<?php

namespace Database\Seeders;

use App\Models\NBAMargin;
use Illuminate\Database\Seeder;

class NBAMarginSeeder extends Seeder
{
    public function run()
    {
        $margins = [
            1 => 50,
            2 => 66,
            3 => 69,
            4 => 61,
            5 => 64,
            6 => 76,
            7 => 86,
            8 => 74,
            9 => 74,
            10 => 54,
            11 => 55,
            12 => 66,
            13 => 44,
            14 => 48,
            15 => 38,
            16 => 46,
            17 => 30,
            18 => 32,
            19 => 27,
            20 => 19,
            21 => 30,
            22 => 21,
            23 => 22,
            24 => 15,
            25 => 16,
            26 => 14,
            27 => 15,
            28 => 12,
            29 => 10,
            30 => 7,
            31 => 5,
            32 => 9,
            33 => 8,
            34 => 6,
            35 => 5,
            36 => 9,
            37 => 5,
            38 => 6,
            39 => 2,
            40 => 3,
            41 => 4,
            42 => 1,
            43 => 1,
            44 => 3,
            45 => 2,
            48 => 1,
            49 => 1,
            50 => 2,
            51 => 1,
            52 => 1,
            53 => 1,
            60 => 1,
            62 => 1,
        ];

        foreach ($margins as $margin => $occurrences) {
            NBAMargin::create([
                'margin' => $margin,
                'occurrences' => $occurrences
            ]);
        }
    }
} 