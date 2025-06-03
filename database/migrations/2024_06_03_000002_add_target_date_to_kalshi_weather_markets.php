<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            $table->date('target_date')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('kalshi_weather_markets', function (Blueprint $table) {
            $table->dropColumn('target_date');
        });
    }
}; 