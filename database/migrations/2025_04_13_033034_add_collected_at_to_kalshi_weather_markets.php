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
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            $table->timestamp('collected_at')->after('last_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            $table->dropColumn('collected_at');
        });
    }
}; 