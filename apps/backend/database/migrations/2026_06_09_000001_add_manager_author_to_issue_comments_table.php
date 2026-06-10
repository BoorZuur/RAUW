<?php

use App\Enums\ActorType;
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
        if (! Schema::hasColumn('issue_comments', 'manager_id')) {
            Schema::table('issue_comments', function (Blueprint $table) {
                $table->foreignId('manager_id')
                    ->nullable()
                    ->after('officer_id')
                    ->constrained('managers')
                    ->nullOnDelete();
            });
        }

        if (! $this->indexExists('issue_comments', 'issue_comments_manager_created_at_idx')) {
            Schema::table('issue_comments', function (Blueprint $table) {
                $table->index(['manager_id', 'created_at'], 'issue_comments_manager_created_at_idx');
            });
        }

        $this->setAuthorTypeEnum(ActorType::commentAuthorValues());
    }

    /**
     * Reverse the migrations.
     *
     * Rollback narrows author_type to user|officer only. Fails if any manager-authored
     * comments exist — delete or reassign those rows before rolling back.
     */
    public function down(): void
    {
        $this->setAuthorTypeEnum(ActorType::issueParticipantValues());

        Schema::table('issue_comments', function (Blueprint $table) {
            $table->dropIndex('issue_comments_manager_created_at_idx');
            $table->dropConstrainedForeignId('manager_id');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $row = DB::selectOne(
                "SELECT 1 FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $index],
            );

            return $row !== null;
        }

        return collect(Schema::getIndexes($table))
            ->contains(static fn (array $definition): bool => ($definition['name'] ?? null) === $index);
    }

    /**
     * @param  list<string>  $values
     */
    private function setAuthorTypeEnum(array $values): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $quoted = implode("','", $values);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE issue_comments MODIFY author_type ENUM('{$quoted}') NOT NULL");

            return;
        }

        if ($driver === 'sqlite') {
            // Laravel stores SQLite enums as unconstrained varchar columns.
            return;
        }

        if ($driver === 'pgsql') {
            $enumValues = implode(', ', array_map(
                static fn (string $value): string => "'{$value}'",
                $values,
            ));
            DB::statement('ALTER TABLE issue_comments DROP CONSTRAINT IF EXISTS issue_comments_author_type_check');
            DB::statement("ALTER TABLE issue_comments ADD CONSTRAINT issue_comments_author_type_check CHECK (author_type IN ({$enumValues}))");
        }
    }
};
