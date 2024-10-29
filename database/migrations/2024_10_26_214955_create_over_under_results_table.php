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
        Schema::create('over_under_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('over_under_id')->constrained('over_unders');
            $table->foreignId('game_id')->constrained('games');
            $table->decimal('actual_total', 5, 1);
            $table->boolean('went_over');
            $table->decimal('profit_loss', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('over_under_results');
    }
};
