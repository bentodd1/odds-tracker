<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ncaaf_margins', function (Blueprint $table) {
            $table->id();
            $table->integer('margin');
            $table->integer('occurrences');
            $table->decimal('cumulative_percentage', 5, 2);
            $table->boolean('is_key_number')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ncaaf_margins');
    }
};
