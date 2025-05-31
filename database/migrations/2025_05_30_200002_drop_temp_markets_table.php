<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('kalshi_weather_markets_new');
    }

    public function down(): void
    {
        // No need to recreate the temp table in down()
    }
}; 