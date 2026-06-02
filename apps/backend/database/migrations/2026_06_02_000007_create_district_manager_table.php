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
        // Pivot that lets a manager be assigned to one or more districts. The
        // unique composite key prevents duplicate assignments, while the single
        // column indexes keep district- and manager-scoped lookups fast.
        Schema::create('district_manager', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('managers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['district_id', 'manager_id'], 'district_manager_unique');
            $table->index('district_id', 'district_manager_district_id_idx');
            $table->index('manager_id', 'district_manager_manager_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('district_manager');
    }
};
