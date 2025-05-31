<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accuweather_predictions', function (Blueprint $table) {
            $table->dropForeign(['kalshi_weather_market_id']);
            $table->dropColumn('kalshi_weather_market_id');
        });
    }

    public function down(): void
    {
        Schema::table('accuweather_predictions', function (Blueprint $table) {
            $table->foreignId('kalshi_weather_market_id')->nullable()->constrained('kalshi_weather_markets')->onDelete('set null');
        });
    }
}; 