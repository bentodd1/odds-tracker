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
        Schema::create('dratings_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games');
            $table->decimal('home_win_probability', 5, 2);  // Store as percentage (e.g., 65.40)
            $table->decimal('away_win_probability', 5, 2);
            $table->decimal('home_moneyline', 8, 2)->nullable();
            $table->decimal('away_moneyline', 8, 2)->nullable();
            $table->decimal('home_ev', 8, 2)->nullable();  // Expected value for home team bet
            $table->decimal('away_ev', 8, 2)->nullable();  // Expected value for away team bet
            $table->dateTime('recorded_at');
            $table->timestamps();

            // Add index for performance
            $table->index(['game_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dratings_predictions');
    }
};
