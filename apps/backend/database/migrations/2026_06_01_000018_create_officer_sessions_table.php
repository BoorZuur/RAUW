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
        Schema::create('officer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->constrained('officers')->cascadeOnDelete();
            $table->dateTime('shift_start')->useCurrent();
            $table->dateTime('shift_end')->nullable();
            $table->decimal('start_lat', 10, 8)->nullable();
            $table->decimal('start_lng', 11, 8)->nullable();
            $table->decimal('last_lat', 10, 8)->nullable();
            $table->decimal('last_lng', 11, 8)->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_sessions');
    }
};
