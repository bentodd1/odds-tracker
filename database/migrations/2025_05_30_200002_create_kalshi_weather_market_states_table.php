<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalshi_weather_market_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained('kalshi_weather_markets')->onDelete('cascade');
            $table->string('status');
            $table->timestamp('close_time');
            $table->decimal('yes_ask', 5, 2)->nullable();
            $table->decimal('yes_bid', 5, 2)->nullable();
            $table->decimal('no_ask', 5, 2)->nullable();
            $table->decimal('no_bid', 5, 2)->nullable();
            $table->decimal('volume', 10, 2)->default(0);
            $table->decimal('open_interest', 10, 2)->default(0);
            $table->decimal('liquidity', 10, 2)->default(0);
            $table->decimal('last_price', 5, 2)->nullable();
            $table->timestamp('collected_at');
            $table->timestamps();
            
            $table->index(['market_id', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kalshi_weather_market_states');
    }
}; 