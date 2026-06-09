<?php

use App\Enums\JoinedVia;
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
        $values = JoinedVia::values();
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $quoted = "'".implode("','", $values)."'";
            $default = JoinedVia::Manual->value;

            DB::statement(
                "ALTER TABLE issue_participants MODIFY joined_via ENUM({$quoted}) NOT NULL DEFAULT '{$default}'"
            );

            return;
        }

        Schema::table('issue_participants', function (Blueprint $table) use ($values): void {
            $table->enum('joined_via', $values)->default(JoinedVia::Manual->value)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $legacyValues = [
            JoinedVia::Manual->value,
            JoinedVia::Duplicate->value,
        ];
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $quoted = "'".implode("','", $legacyValues)."'";
            $default = JoinedVia::Manual->value;

            DB::statement(
                "ALTER TABLE issue_participants MODIFY joined_via ENUM({$quoted}) NOT NULL DEFAULT '{$default}'"
            );

            return;
        }

        Schema::table('issue_participants', function (Blueprint $table) use ($legacyValues): void {
            $table->enum('joined_via', $legacyValues)->default(JoinedVia::Manual->value)->change();
        });
    }
};
