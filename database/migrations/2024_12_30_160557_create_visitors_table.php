<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->text('user_agent')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('page_url');
            $table->timestamp('visited_at');
            $table->timestamps();

            // Add index for better performance on common queries
            $table->index(['ip_address', 'visited_at']);
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
