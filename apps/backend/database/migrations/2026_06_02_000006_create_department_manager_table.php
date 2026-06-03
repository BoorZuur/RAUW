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
        // 1. Create the manager department pivot, mirroring department_officer.
        Schema::create('department_manager', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('managers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['department_id', 'manager_id'], 'department_manager_unique');
            $table->index('department_id', 'department_manager_department_id_idx');
            $table->index('manager_id', 'department_manager_manager_id_idx');
        });

        // 2. Backfill the pivot from the legacy singular managers.department_id so
        //    every manager keeps its current department assignment.
        if (Schema::hasColumn('managers', 'department_id')) {
            $assignments = [];

            DB::table('managers')
                ->select('id', 'department_id')
                ->whereNotNull('department_id')
                ->orderBy('id')
                ->each(function (object $manager) use (&$assignments): void {
                    $assignments[] = [
                        'manager_id' => $manager->id,
                        'department_id' => $manager->department_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });

            foreach (array_chunk($assignments, 500) as $chunk) {
                DB::table('department_manager')->insertOrIgnore($chunk);
            }

            // 3. Drop the legacy singular column now that assignments live in the
            //    pivot. Managers without a legacy department_id (nullable/invalid)
            //    are simply left unattached; writes will enforce min:1 going
            //    forward. dropConstrainedForeignId removes the FK and column.
            Schema::table('managers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add the singular department_id column (nullable to tolerate
        //    managers that have no pivot assignment during rollback).
        if (! Schema::hasColumn('managers', 'department_id')) {
            Schema::table('managers', function (Blueprint $table) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('password')
                    ->constrained('departments')
                    ->restrictOnDelete();
            });
        }

        // 2. Restore department_id from the first related pivot department using
        //    deterministic ordering. Rollback cannot preserve managers that have
        //    multiple department assignments; the lowest department_id wins.
        $firstDepartmentByManager = DB::table('department_manager')
            ->select('manager_id', DB::raw('MIN(department_id) as department_id'))
            ->groupBy('manager_id')
            ->pluck('department_id', 'manager_id');

        foreach ($firstDepartmentByManager as $managerId => $departmentId) {
            DB::table('managers')
                ->where('id', $managerId)
                ->update(['department_id' => $departmentId]);
        }

        // 3. Remove the pivot table now that history lives in department_id.
        Schema::dropIfExists('department_manager');
    }
};
