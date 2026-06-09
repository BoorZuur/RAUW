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
        Schema::table('officer_issue_updates', function (Blueprint $table) {
            $table->index(
                ['issue_id', 'created_at'],
                'officer_issue_updates_issue_created_at_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('officer_issue_updates', function (Blueprint $table) {
            $table->dropIndex('officer_issue_updates_issue_created_at_idx');
        });
    }
};
