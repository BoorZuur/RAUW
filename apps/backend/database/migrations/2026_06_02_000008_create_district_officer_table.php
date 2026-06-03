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
        // Pivot that lets an officer be assigned to one or more districts. The
        // unique composite key prevents duplicate assignments, while the single
        // column indexes keep district- and officer-scoped lookups fast.
        Schema::create('district_officer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->foreignId('officer_id')->constrained('officers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['district_id', 'officer_id'], 'district_officer_unique');
            $table->index('district_id', 'district_officer_district_id_idx');
            $table->index('officer_id', 'district_officer_officer_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('district_officer');
    }
};
