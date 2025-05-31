<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            $table->dropUnique('kalshi_weather_markets_ticker_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            $table->unique('ticker');
        });
    }
};
