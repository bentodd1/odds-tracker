<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('over_under_results');

        Schema::create('over_under_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('over_under_id')->constrained('over_unders');
            $table->foreignId('score_id')->constrained('scores');
            $table->decimal('total_points', 5, 1);
            $table->enum('result', ['over', 'under', 'push']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('over_under_results');
    }
};
