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
        Schema::table('officer_sessions', function (Blueprint $table) {
            $table->index(['shift_start', 'id'], 'officer_sessions_shift_start_id_idx');
            $table->index(['officer_id', 'is_active'], 'officer_sessions_officer_id_is_active_idx');
            $table->index(['officer_id', 'shift_start', 'id'], 'officer_sessions_officer_id_shift_start_id_idx');
            $table->index(['hub_id', 'shift_start', 'id'], 'officer_sessions_hub_id_shift_start_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('officer_sessions', function (Blueprint $table) {
            $table->dropIndex('officer_sessions_shift_start_id_idx');
            $table->dropIndex('officer_sessions_officer_id_is_active_idx');
            $table->dropIndex('officer_sessions_officer_id_shift_start_id_idx');
            $table->dropIndex('officer_sessions_hub_id_shift_start_id_idx');
        });
    }
};
