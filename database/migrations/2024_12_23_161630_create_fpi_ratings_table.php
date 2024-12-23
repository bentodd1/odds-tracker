<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fpi_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained();
            $table->decimal('rating', 8, 2);
            $table->integer('revision');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->unique(['team_id', 'revision']);
            $table->index(['team_id', 'recorded_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fpi_ratings');
    }
};
