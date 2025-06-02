<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nws_weather_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->date('prediction_date');
            $table->time('prediction_time')->nullable();
            $table->date('target_date');
            $table->integer('predicted_high');
            $table->integer('predicted_low');
            $table->integer('actual_high')->nullable();
            $table->integer('actual_low')->nullable();
            $table->integer('high_difference')->nullable();
            $table->integer('low_difference')->nullable();
            $table->integer('forecast_hour')->nullable(); // The hour of the day when this forecast was made
            $table->foreignId('kalshi_weather_market_id')->nullable()->constrained('kalshi_weather_markets')->onDelete('set null');
            $table->timestamps();
            
            // Add indexes for common queries
            $table->index(['city', 'target_date']);
            $table->index('prediction_date');
            $table->index('forecast_hour');
            
            // Add unique constraint to prevent duplicate entries for the same city, target date, and forecast hour
            $table->unique(['city', 'target_date', 'forecast_hour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nws_weather_predictions');
    }
}; 