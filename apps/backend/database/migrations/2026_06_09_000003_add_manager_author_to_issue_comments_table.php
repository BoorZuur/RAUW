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

        if (! $this->indexExists('issue_comments_manager_created_at_idx')) {
            Schema::table('issue_comments', function (Blueprint $table) {
                $table->index(['manager_id', 'created_at'], 'issue_comments_manager_created_at_idx');
            });
        }

        $this->setAuthorTypeEnum(ActorType::commentAuthorValues());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->setAuthorTypeEnum(ActorType::issueParticipantValues());

        if ($this->indexExists('issue_comments_manager_created_at_idx')) {
            Schema::table('issue_comments', function (Blueprint $table) {
                $table->dropIndex('issue_comments_manager_created_at_idx');
            });
        }

        if (Schema::hasColumn('issue_comments', 'manager_id')) {
            Schema::table('issue_comments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('manager_id');
            });
        }
    }

    private function indexExists(string $name): bool
    {
        foreach (Schema::getIndexes('issue_comments') as $index) {
            if ($index['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $values
     */
    private function setAuthorTypeEnum(array $values): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $enumList = "'".implode("','", $values)."'";

        DB::statement("ALTER TABLE issue_comments MODIFY author_type ENUM({$enumList}) NOT NULL");
    }
};
