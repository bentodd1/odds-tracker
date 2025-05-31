<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_temperatures', function (Blueprint $table) {
            $table->id();
            $table->string('location');
            $table->date('date');
            $table->integer('high_temperature')->nullable();
            $table->integer('low_temperature')->nullable();
            $table->string('source')->default('accuweather'); // Could be accuweather, noaa, etc.
            $table->timestamp('collected_at');
            $table->timestamps();

            // Add unique constraint to prevent duplicate entries
            $table->unique(['location', 'date', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_temperatures');
    }
}; 