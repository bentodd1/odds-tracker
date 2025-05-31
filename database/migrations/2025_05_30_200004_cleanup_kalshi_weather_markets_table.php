<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            // Remove columns that are now in market_states
            $table->dropColumn([
                'yes_ask',
                'yes_bid',
                'no_ask',
                'no_bid',
                'volume',
                'open_interest',
            ]);

            // Add temperature columns
            $table->integer('low_temperature')->nullable()->after('single_strike');
            $table->integer('high_temperature')->nullable()->after('low_temperature');
        });
    }

    public function down(): void
    {
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            // Re-add the removed columns
            $table->float('yes_ask')->nullable();
            $table->float('yes_bid')->nullable();
            $table->float('no_ask')->nullable();
            $table->float('no_bid')->nullable();
            $table->float('volume')->default(0);
            $table->float('open_interest')->default(0);

            // Remove the temperature columns
            $table->dropColumn(['low_temperature', 'high_temperature']);
        });
    }
}; 