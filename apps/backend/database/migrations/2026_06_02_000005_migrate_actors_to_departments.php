<?php

use App\Enums\Department;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The canonical department rows that actors are assigned to.
     *
     * The legacy `beide` (Both) value is intentionally not a department of its
     * own. Managers must now belong to exactly one department, so legacy
     * `beide` managers fall back deterministically to `wijkbeheer`.
     *
     * @var array<string, string>
     */
    private array $departments = [
        'wijkbeheer' => 'Wijkbeheer',
        'boa_jeugd' => 'BOA / Jeugd',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure the canonical department rows exist.
        foreach ($this->departments as $code => $name) {
            DB::table('departments')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $departmentIds = DB::table('departments')
            ->whereIn('code', array_keys($this->departments))
            ->pluck('id', 'code');

        // 2. Add a nullable department_id to managers so existing rows migrate
        //    safely before we enforce the non-null constraint.
        if (! Schema::hasColumn('managers', 'department_id')) {
            Schema::table('managers', function (Blueprint $table) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('password')
                    ->constrained('departments')
                    ->restrictOnDelete();
            });
        }

        // 3. Backfill manager department_id from the legacy enum column.
        //    `beide` collapses to `wijkbeheer` as the deterministic fallback.
        if (Schema::hasColumn('managers', 'department')) {
            DB::table('managers')->select('id', 'department')->orderBy('id')->each(
                function (object $manager) use ($departmentIds): void {
                    $code = match ($manager->department) {
                        Department::BoaYouth->value => 'boa_jeugd',
                        default => 'wijkbeheer',
                    };

                    if (! isset($departmentIds[$code])) {
                        return;
                    }

                    DB::table('managers')
                        ->where('id', $manager->id)
                        ->update(['department_id' => $departmentIds[$code]]);
                }
            );
        }

        // 4. Guarantee every manager has a department before going non-null.
        $fallbackId = $departmentIds['wijkbeheer'] ?? null;

        if ($fallbackId !== null) {
            DB::table('managers')
                ->whereNull('department_id')
                ->update(['department_id' => $fallbackId]);
        }

        // 5. Drop the legacy enum column now that history lives in department_id.
        if (Schema::hasColumn('managers', 'department')) {
            Schema::table('managers', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }

        // 6. Enforce the exact-one invariant: department_id is non-nullable.
        Schema::table('managers', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable(false)->change();
        });

        // 7. Backfill all existing officers by attaching both canonical
        //    departments, because officers previously had no department signal.
        $assignments = [];

        DB::table('officers')->select('id')->orderBy('id')->each(
            function (object $officer) use ($departmentIds, &$assignments): void {
                foreach ($departmentIds as $departmentId) {
                    $assignments[] = [
                        'officer_id' => $officer->id,
                        'department_id' => $departmentId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        );

        foreach (array_chunk($assignments, 500) as $chunk) {
            DB::table('department_officer')->insertOrIgnore($chunk);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the legacy enum column (nullable to tolerate missing data).
        if (! Schema::hasColumn('managers', 'department')) {
            Schema::table('managers', function (Blueprint $table) {
                $table->enum('department', Department::values())
                    ->default(Department::Both->value)
                    ->after('password');
            });

            // Best-effort backfill from department_id back to the legacy enum.
            $codeById = DB::table('departments')->pluck('code', 'id');

            DB::table('managers')->select('id', 'department_id')->orderBy('id')->each(
                function (object $manager) use ($codeById): void {
                    $code = $codeById[$manager->department_id] ?? null;

                    if ($code === null) {
                        return;
                    }

                    DB::table('managers')
                        ->where('id', $manager->id)
                        ->update(['department' => $code]);
                }
            );
        }

        // Remove the manager foreign key and column.
        if (Schema::hasColumn('managers', 'department_id')) {
            Schema::table('managers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        // Remove officer department assignments.
        DB::table('department_officer')->truncate();
    }
};
