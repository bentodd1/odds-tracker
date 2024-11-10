<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('spread_results');

        Schema::create('spread_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spread_id')->constrained('spreads');
            $table->foreignId('score_id')->constrained('scores');
            $table->enum('result', ['home_covered', 'away_covered', 'push']);
            $table->timestamps();

            // Optional: Add unique constraint to prevent duplicate results
            $table->unique(['spread_id', 'score_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('spread_results');
    }
};
