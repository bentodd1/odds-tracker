<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFpiAdjustedSpreadToSpreadResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('spread_results', function (Blueprint $table) {
            $table->decimal('fpi_adjusted_spread', 8, 1)->nullable()->after('fpi_spread');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('spread_results', function (Blueprint $table) {
            $table->dropColumn('fpi_adjusted_spread');
        });
    }
}
