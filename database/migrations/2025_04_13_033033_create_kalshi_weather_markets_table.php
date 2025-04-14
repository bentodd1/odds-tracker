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
        Schema::create('kalshi_weather_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('kalshi_weather_categories')->onDelete('set null');
            $table->string('event_ticker');
            $table->string('ticker')->unique();
            $table->string('title');
            $table->string('status');
            $table->timestamp('close_time');
            $table->float('yes_ask')->nullable();
            $table->float('yes_bid')->nullable();
            $table->float('no_ask')->nullable();
            $table->float('no_bid')->nullable();
            $table->float('volume')->default(0);
            $table->float('open_interest')->default(0);
            $table->float('liquidity')->default(0);
            $table->text('rules_primary')->nullable();
            $table->timestamp('last_updated_at');
            $table->timestamps();
            
            $table->index('event_ticker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kalshi_weather_markets');
    }
}; 