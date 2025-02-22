<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('spread_results', function (Blueprint $table) {
            $table->decimal('fpi_spread', 4, 1)->nullable();
            $table->boolean('fpi_correctly_predicted')->nullable();
        });
    }

    public function down()
    {
        Schema::table('spread_results', function (Blueprint $table) {
            $table->dropColumn('fpi_spread');
            $table->dropColumn('fpi_correctly_predicted');
        });
    }
}; 