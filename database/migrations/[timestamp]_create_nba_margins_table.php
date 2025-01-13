<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('nba_margins', function (Blueprint $table) {
            $table->id();
            $table->integer('margin');
            $table->integer('occurrences');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nba_margins');
    }
}; 