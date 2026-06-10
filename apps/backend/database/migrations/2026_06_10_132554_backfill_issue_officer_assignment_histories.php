<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill issue_officer_assignment_histories from issues where assigned_officer_id is not null
        DB::table('issues')
            ->whereNotNull('assigned_officer_id')
            ->orderBy('id')
            ->chunk(100, function ($issues) {
                $inserts = [];
                $now = now();
                foreach ($issues as $issue) {
                    $inserts[] = [
                        'issue_id' => $issue->id,
                        'officer_id' => $issue->assigned_officer_id,
                        'assigned_at' => $issue->updated_at ?? $now,
                    ];
                }

                // Use insertOrIgnore to handle potential duplicates if any
                DB::table('issue_officer_assignment_histories')->insertOrIgnore($inserts);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for data backfill
    }
};
