<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nws_weather_predictions')) {
            try {
                Schema::table('nws_weather_predictions', function (Blueprint $table) {
                    $table->dropUnique('nws_weather_predictions_city_target_date_forecast_hour_unique');
                });
            } catch (\Exception $e) {
                // Index doesn't exist, which is fine
            }
        }
    }

    public function down(): void
    {
        Schema::table('nws_weather_predictions', function (Blueprint $table) {
            $table->unique(['city', 'target_date', 'forecast_hour'], 'nws_weather_predictions_city_target_date_forecast_hour_unique');
        });
    }
}; 