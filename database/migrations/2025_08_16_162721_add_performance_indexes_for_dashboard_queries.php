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

        Schema::table('over_unders', function (Blueprint $table) {
            // Indexes similar to spreads and money_lines for consistency
            $table->index(['casino_id', 'created_at'], 'over_unders_casino_created_idx');
            $table->index('recorded_at', 'over_unders_recorded_at_idx');
            $table->index(['game_id', 'created_at'], 'over_unders_game_created_idx');
        });

        // Add indexes to all margin tables for margin calculations
        Schema::table('ncaaf_margins', function (Blueprint $table) {
            // Critical indexes for margin calculations in GameTransformationService
            $table->index('margin', 'ncaaf_margins_margin_idx');
            $table->index(['margin', 'occurrences'], 'ncaaf_margins_margin_occurrences_idx');
        });

        Schema::table('nfl_margins', function (Blueprint $table) {
            $table->index('margin', 'nfl_margins_margin_idx');
            $table->index(['margin', 'occurrences'], 'nfl_margins_margin_occurrences_idx');
        });

        Schema::table('ncaab_margins', function (Blueprint $table) {
            $table->index('margin', 'ncaab_margins_margin_idx');
            $table->index(['margin', 'occurrences'], 'ncaab_margins_margin_occurrences_idx');
        });

        Schema::table('mlb_margins', function (Blueprint $table) {
            $table->index('margin', 'mlb_margins_margin_idx');
            $table->index(['margin', 'occurrences'], 'mlb_margins_margin_occurrences_idx');
        });

        // Check if nba_margins table exists and add indexes
        if (Schema::hasTable('nba_margins')) {
            Schema::table('nba_margins', function (Blueprint $table) {
                $table->index('margin', 'nba_margins_margin_idx');
                $table->index(['margin', 'occurrences'], 'nba_margins_margin_occurrences_idx');
            });
        }

        Schema::table('casinos', function (Blueprint $table) {
            // Index for filtering active casinos
            $table->index('is_active', 'casinos_is_active_idx');
            $table->index('name', 'casinos_name_idx');
        });

        Schema::table('sports', function (Blueprint $table) {
            // Index for filtering by sport title (used in dashboard queries)
            $table->index('title', 'sports_title_idx');
            $table->index('active', 'sports_active_idx');
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

        Schema::table('over_unders', function (Blueprint $table) {
            $table->dropIndex('over_unders_casino_created_idx');
            $table->dropIndex('over_unders_recorded_at_idx');
            $table->dropIndex('over_unders_game_created_idx');
        });

        Schema::table('ncaaf_margins', function (Blueprint $table) {
            $table->dropIndex('ncaaf_margins_margin_idx');
            $table->dropIndex('ncaaf_margins_margin_occurrences_idx');
        });

        Schema::table('nfl_margins', function (Blueprint $table) {
            $table->dropIndex('nfl_margins_margin_idx');
            $table->dropIndex('nfl_margins_margin_occurrences_idx');
        });

        Schema::table('ncaab_margins', function (Blueprint $table) {
            $table->dropIndex('ncaab_margins_margin_idx');
            $table->dropIndex('ncaab_margins_margin_occurrences_idx');
        });

        Schema::table('mlb_margins', function (Blueprint $table) {
            $table->dropIndex('mlb_margins_margin_idx');
            $table->dropIndex('mlb_margins_margin_occurrences_idx');
        });

        if (Schema::hasTable('nba_margins')) {
            Schema::table('nba_margins', function (Blueprint $table) {
                $table->dropIndex('nba_margins_margin_idx');
                $table->dropIndex('nba_margins_margin_occurrences_idx');
            });
        }

        Schema::table('casinos', function (Blueprint $table) {
            $table->dropIndex('casinos_is_active_idx');
            $table->dropIndex('casinos_name_idx');
        });

        Schema::table('sports', function (Blueprint $table) {
            $table->dropIndex('sports_title_idx');
            $table->dropIndex('sports_active_idx');
        });
    }
};