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
            $table->boolean('fpi_better_than_spread')->nullable()->after('fpi_correctly_predicted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spread_results', function (Blueprint $table) {
            $table->dropColumn('fpi_better_than_spread');
        });
    }
}; 