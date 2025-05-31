<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            $table->foreignId('weather_temperature_id')
                ->nullable()
                ->after('high_temperature')
                ->constrained('weather_temperatures')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            $table->dropForeign(['weather_temperature_id']);
            $table->dropColumn('weather_temperature_id');
        });
    }
}; 