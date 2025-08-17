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
        if (Schema::hasTable('kalshi_weather_markets')) {
            Schema::table('kalshi_weather_markets', function (Blueprint $table) {
                if (!Schema::hasColumn('kalshi_weather_markets', 'collected_at')) {
                    $table->timestamp('collected_at')->after('last_updated_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('kalshi_weather_markets') && Schema::hasColumn('kalshi_weather_markets', 'collected_at')) {
            Schema::table('kalshi_weather_markets', function (Blueprint $table) {
                $table->dropColumn('collected_at');
            });
        }
    }
}; 