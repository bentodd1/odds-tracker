<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('spread_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spread_id')->constrained('spreads');
            $table->foreignId('game_id')->constrained('games');
            $table->integer('home_score');
            $table->integer('away_score');
            $table->decimal('spread', 5, 1);  // The spread at time of bet
            $table->decimal('actual_margin', 5, 1);  // Final score margin (home_score - away_score)
            $table->enum('result', ['home_covered', 'away_covered', 'push']);
            $table->decimal('home_profit_loss', 10, 2)->nullable();
            $table->decimal('away_profit_loss', 10, 2)->nullable();
            $table->timestamps();

            // Add index for performance
            $table->index(['spread_id', 'game_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spread_results');
    }
};
