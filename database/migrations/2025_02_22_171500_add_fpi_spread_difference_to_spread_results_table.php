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
        Schema::table('spread_results', function (Blueprint $table) {
            $table->float('fpi_spread_difference')->nullable()->after('fpi_spread');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spread_results', function (Blueprint $table) {
            $table->dropColumn('fpi_spread_difference');
        });
    }
}; 