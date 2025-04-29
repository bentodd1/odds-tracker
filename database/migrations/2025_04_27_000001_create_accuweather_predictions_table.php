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
        Schema::create('accuweather_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('location_url');
            $table->date('prediction_date');
            $table->time('prediction_time')->nullable();
            $table->date('target_date');
            $table->integer('predicted_high');
            $table->integer('predicted_low');
            $table->integer('actual_high')->nullable();
            $table->integer('actual_low')->nullable();
            $table->integer('high_difference')->nullable();
            $table->integer('low_difference')->nullable();
            $table->foreignId('kalshi_weather_market_id')->nullable()->constrained('kalshi_weather_markets')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['city', 'target_date']);
            $table->index('prediction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accuweather_predictions');
    }
}; 