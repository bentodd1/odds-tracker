<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kalshi_weather_markets')) {
            Schema::table('kalshi_weather_markets', function (Blueprint $table) {
                if (!Schema::hasColumn('kalshi_weather_markets', 'target_date')) {
                    $table->date('target_date')->nullable()->after('location');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kalshi_weather_markets') && Schema::hasColumn('kalshi_weather_markets', 'target_date')) {
            Schema::table('kalshi_weather_markets', function (Blueprint $table) {
                $table->dropColumn('target_date');
            });
        }
    }
}; 