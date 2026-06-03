<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Backfill the manager pivot from the legacy singular
        //    managers.district_id so every manager keeps its current district
        //    assignment before the column is dropped.
        if (Schema::hasColumn('managers', 'district_id')) {
            $this->backfillPivot('managers', 'manager_id', 'district_manager');

            // Drop the legacy singular column now that assignments live in the
            // pivot. dropConstrainedForeignId removes the FK and the column.
            Schema::table('managers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('district_id');
            });
        }

        // 2. Backfill the officer pivot from the legacy singular
        //    officers.district_id, then drop that column as well.
        if (Schema::hasColumn('officers', 'district_id')) {
            $this->backfillPivot('officers', 'officer_id', 'district_officer');

            Schema::table('officers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('district_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add the nullable singular district_id columns (nullable to
        //    tolerate actors that have no pivot assignment during rollback).
        if (! Schema::hasColumn('managers', 'district_id')) {
            Schema::table('managers', function (Blueprint $table) {
                $table->foreignId('district_id')
                    ->nullable()
                    ->after('password')
                    ->constrained('districts')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('officers', 'district_id')) {
            Schema::table('officers', function (Blueprint $table) {
                $table->foreignId('district_id')
                    ->nullable()
                    ->after('password')
                    ->constrained('districts')
                    ->nullOnDelete();
            });
        }

        // 2. Restore district_id from the first related pivot district using
        //    deterministic ordering. Rollback cannot preserve actors that have
        //    multiple district assignments; the lowest district_id wins.
        $this->restoreSingularColumn('district_manager', 'manager_id', 'managers');
        $this->restoreSingularColumn('district_officer', 'officer_id', 'officers');

        // Note: the district_manager and district_officer pivot tables are
        // dropped by their dedicated create migrations' down() methods when the
        // batch is rolled back.
    }

    /**
     * Copy every non-null singular district assignment from an actor table into
     * the matching pivot, skipping duplicates via insertOrIgnore.
     */
    private function backfillPivot(string $actorTable, string $actorKey, string $pivotTable): void
    {
        $assignments = [];

        DB::table($actorTable)
            ->select('id', 'district_id')
            ->whereNotNull('district_id')
            ->orderBy('id')
            ->each(function (object $actor) use (&$assignments, $actorKey): void {
                $assignments[] = [
                    $actorKey => $actor->id,
                    'district_id' => $actor->district_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

        foreach (array_chunk($assignments, 500) as $chunk) {
            DB::table($pivotTable)->insertOrIgnore($chunk);
        }
    }

    /**
     * Restore an actor's singular district_id from the lowest related district
     * in its pivot, using deterministic ordering.
     */
    private function restoreSingularColumn(string $pivotTable, string $actorKey, string $actorTable): void
    {
        $firstDistrictByActor = DB::table($pivotTable)
            ->select($actorKey, DB::raw('MIN(district_id) as district_id'))
            ->groupBy($actorKey)
            ->pluck('district_id', $actorKey);

        foreach ($firstDistrictByActor as $actorId => $districtId) {
            DB::table($actorTable)
                ->where('id', $actorId)
                ->update(['district_id' => $districtId]);
        }
    }
};
