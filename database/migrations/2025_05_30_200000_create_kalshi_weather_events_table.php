<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalshi_weather_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('kalshi_weather_categories')->onDelete('cascade');
            $table->string('event_ticker')->unique();
            $table->date('target_date');
            $table->string('location');
            $table->timestamps();
            
            $table->index(['category_id', 'target_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kalshi_weather_events');
    }
}; 