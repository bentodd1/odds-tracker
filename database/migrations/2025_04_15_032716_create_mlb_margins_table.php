<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mlb_margins', function (Blueprint $table) {
            $table->id();
            $table->integer('margin');
            $table->integer('occurrences');
            $table->decimal('cumulative_percentage', 5, 2);
            $table->boolean('is_key_number')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlb_margins');
    }
};
