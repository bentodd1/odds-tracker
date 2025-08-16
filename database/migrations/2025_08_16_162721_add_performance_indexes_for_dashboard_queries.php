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
        Schema::table('games', function (Blueprint $table) {
            // Critical index for dashboard queries that filter by sport and commence_time
            $table->index(['sport_id', 'commence_time'], 'games_sport_commence_idx');
            // Index for commence_time filtering
            $table->index('commence_time', 'games_commence_time_idx');
        });

        Schema::table('spreads', function (Blueprint $table) {
            // Critical index for filtering spreads by casino and date
            $table->index(['casino_id', 'created_at'], 'spreads_casino_created_idx');
            // Index for recorded_at filtering and ordering
            $table->index('recorded_at', 'spreads_recorded_at_idx');
            // Composite index for game_id with created_at for efficient filtering
            $table->index(['game_id', 'created_at'], 'spreads_game_created_idx');
        });

        Schema::table('money_lines', function (Blueprint $table) {
            // Critical index for filtering money lines by casino and date
            $table->index(['casino_id', 'created_at'], 'money_lines_casino_created_idx');
            // Index for recorded_at filtering and ordering
            $table->index('recorded_at', 'money_lines_recorded_at_idx');
            // Composite index for game_id with created_at for efficient filtering
            $table->index(['game_id', 'created_at'], 'money_lines_game_created_idx');
        });

        Schema::table('fpi_ratings', function (Blueprint $table) {
            // Optimize the latestFpi() relationship query
            $table->index(['team_id', 'revision', 'recorded_at'], 'fpi_ratings_team_revision_recorded_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex('games_sport_commence_idx');
            $table->dropIndex('games_commence_time_idx');
        });

        Schema::table('spreads', function (Blueprint $table) {
            $table->dropIndex('spreads_casino_created_idx');
            $table->dropIndex('spreads_recorded_at_idx');
            $table->dropIndex('spreads_game_created_idx');
        });

        Schema::table('money_lines', function (Blueprint $table) {
            $table->dropIndex('money_lines_casino_created_idx');
            $table->dropIndex('money_lines_recorded_at_idx');
            $table->dropIndex('money_lines_game_created_idx');
        });

        Schema::table('fpi_ratings', function (Blueprint $table) {
            $table->dropIndex('fpi_ratings_team_revision_recorded_idx');
        });
    }
};