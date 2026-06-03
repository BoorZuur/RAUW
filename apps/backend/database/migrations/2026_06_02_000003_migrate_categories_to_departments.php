<?php

use App\Enums\Department;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The canonical department rows derived from the legacy enum.
     *
     * The legacy `beide` (Both) value is intentionally not a department of its
     * own; categories that used it are assigned to both real departments via
     * the many-to-many pivot.
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

        // 2. Add the main-category priority column (lower number = higher priority).
        if (! Schema::hasColumn('categories', 'priority')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedTinyInteger('priority')->nullable()->after('weight');
            });
        }

        // 3. Backfill the pivot from the legacy enum column.
        if (Schema::hasColumn('categories', 'department')) {
            $departmentIds = DB::table('departments')
                ->whereIn('code', array_keys($this->departments))
                ->pluck('id', 'code');

            $assignments = [];

            DB::table('categories')->select('id', 'department')->orderBy('id')->each(
                function (object $category) use ($departmentIds, &$assignments): void {
                    $codes = match ($category->department) {
                        Department::Both->value => array_keys($this->departments),
                        default => [$category->department],
                    };

                    foreach ($codes as $code) {
                        if (! isset($departmentIds[$code])) {
                            continue;
                        }

                        $assignments[] = [
                            'category_id' => $category->id,
                            'department_id' => $departmentIds[$code],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            );

            foreach (array_chunk($assignments, 500) as $chunk) {
                DB::table('category_department')->insertOrIgnore($chunk);
            }

            // 4. Drop the legacy enum column now that history lives in the pivot.
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the legacy enum column (nullable to tolerate missing data).
        if (! Schema::hasColumn('categories', 'department')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->enum('department', Department::values())->nullable()->after('name');
            });

            // Best-effort backfill from the pivot, collapsing multi-department
            // assignments back to the legacy `beide` value where applicable.
            $codeById = DB::table('departments')->pluck('code', 'id');

            DB::table('categories')->select('id')->orderBy('id')->each(
                function (object $category) use ($codeById): void {
                    $codes = DB::table('category_department')
                        ->where('category_id', $category->id)
                        ->pluck('department_id')
                        ->map(fn ($id) => $codeById[$id] ?? null)
                        ->filter()
                        ->values();

                    if ($codes->isEmpty()) {
                        return;
                    }

                    $value = $codes->count() > 1
                        ? Department::Both->value
                        : $codes->first();

                    DB::table('categories')
                        ->where('id', $category->id)
                        ->update(['department' => $value]);
                }
            );
        }

        if (Schema::hasColumn('categories', 'priority')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('priority');
            });
        }

        DB::table('departments')
            ->whereIn('code', array_keys($this->departments))
            ->delete();
    }
};
