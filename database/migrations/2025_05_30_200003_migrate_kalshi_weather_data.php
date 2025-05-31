<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create the events table if it doesn't exist
        if (!Schema::hasTable('kalshi_weather_events')) {
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

        // Create the market states table if it doesn't exist
        if (!Schema::hasTable('kalshi_weather_market_states')) {
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

        // Add new columns to existing markets table if they don't exist
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            if (!Schema::hasColumn('kalshi_weather_markets', 'event_id')) {
                $table->foreignId('event_id')->nullable()->after('category_id')->constrained('kalshi_weather_events')->onDelete('cascade');
            }
            if (!Schema::hasColumn('kalshi_weather_markets', 'strike_type')) {
                $table->string('strike_type')->nullable()->after('title');
            }
            if (!Schema::hasColumn('kalshi_weather_markets', 'floor_strike')) {
                $table->integer('floor_strike')->nullable()->after('strike_type');
            }
            if (!Schema::hasColumn('kalshi_weather_markets', 'cap_strike')) {
                $table->integer('cap_strike')->nullable()->after('floor_strike');
            }
            if (!Schema::hasColumn('kalshi_weather_markets', 'single_strike')) {
                $table->integer('single_strike')->nullable()->after('cap_strike');
            }
            if (!Schema::hasColumn('kalshi_weather_markets', 'rules_secondary')) {
                $table->text('rules_secondary')->nullable()->after('rules_primary');
            }
        });
    }

    public function down(): void
    {
        // Remove new columns from markets table
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            if (Schema::hasColumn('kalshi_weather_markets', 'event_id')) {
                $table->dropForeign(['event_id']);
                $table->dropColumn('event_id');
            }
            if (Schema::hasColumn('kalshi_weather_markets', 'strike_type')) {
                $table->dropColumn('strike_type');
            }
            if (Schema::hasColumn('kalshi_weather_markets', 'floor_strike')) {
                $table->dropColumn('floor_strike');
            }
            if (Schema::hasColumn('kalshi_weather_markets', 'cap_strike')) {
                $table->dropColumn('cap_strike');
            }
            if (Schema::hasColumn('kalshi_weather_markets', 'single_strike')) {
                $table->dropColumn('single_strike');
            }
            if (Schema::hasColumn('kalshi_weather_markets', 'rules_secondary')) {
                $table->dropColumn('rules_secondary');
            }
        });

        // Drop new tables if they exist
        Schema::dropIfExists('kalshi_weather_market_states');
        Schema::dropIfExists('kalshi_weather_events');
    }
}; 