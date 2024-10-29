<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('money_line_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('money_line_id')->constrained('money_lines');
            $table->foreignId('game_id')->constrained('games');
            $table->boolean('home_won');
            $table->decimal('profit_loss', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('money_line_results');
    }
};
