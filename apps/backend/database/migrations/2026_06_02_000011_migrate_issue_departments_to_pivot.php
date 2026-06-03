<?php

use App\Enums\Department;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The canonical department codes that back the legacy enum.
     *
     * The legacy `beide` (Both) value is intentionally not a department of its
     * own; issues that used it are assigned to both real departments via the
     * many-to-many pivot.
     *
     * @var list<string>
     */
    private array $departmentCodes = [
        'wijkbeheer',
        'boa_jeugd',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('issues', 'department')) {
            return;
        }

        $departmentIds = DB::table('departments')
            ->whereIn('code', $this->departmentCodes)
            ->pluck('id', 'code');

        $assignments = [];

        DB::table('issues')->select('id', 'department')->orderBy('id')->each(
            function (object $issue) use ($departmentIds, &$assignments): void {
                $codes = match ($issue->department) {
                    Department::Both->value => $this->departmentCodes,
                    default => [$issue->department],
                };

                foreach ($codes as $code) {
                    if (! isset($departmentIds[$code])) {
                        continue;
                    }

                    $assignments[] = [
                        'issue_id' => $issue->id,
                        'department_id' => $departmentIds[$code],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        );

        foreach (array_chunk($assignments, 500) as $chunk) {
            DB::table('department_issue')->insertOrIgnore($chunk);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The legacy `department` enum column remains the source for re-seeding,
        // so simply clear the pivot rows backfilled by this migration.
        DB::table('department_issue')->delete();
    }
};
