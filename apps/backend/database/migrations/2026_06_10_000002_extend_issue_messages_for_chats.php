<?php

use App\Enums\IssueMessageSenderType;
use App\Enums\IssueMessageType;
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
        Schema::table('issue_messages', function (Blueprint $table): void {
            $table->foreignId('issue_chat_id')
                ->after('id')
                ->constrained('issue_chats')
                ->cascadeOnDelete();
            $table->enum('message_type', IssueMessageType::values())
                ->default(IssueMessageType::Message->value)
                ->after('issue_chat_id');
            $table->json('meta')->nullable()->after('content');
        });

        Schema::table('issue_messages', function (Blueprint $table): void {
            $table->text('content')->nullable()->change();
        });

        $senderValues = IssueMessageSenderType::values();
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $quoted = "'".implode("','", $senderValues)."'";

            DB::statement(
                "ALTER TABLE issue_messages MODIFY sender_type ENUM({$quoted}) NOT NULL"
            );
        } else {
            Schema::table('issue_messages', function (Blueprint $table) use ($senderValues): void {
                $table->enum('sender_type', $senderValues)->change();
            });
        }

        Schema::table('issue_messages', function (Blueprint $table): void {
            $table->index(['issue_chat_id', 'created_at'], 'issue_messages_chat_created_at_idx');
            $table->index(['issue_chat_id', 'is_read', 'created_at'], 'issue_messages_chat_read_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_messages', function (Blueprint $table): void {
            $table->dropIndex('issue_messages_chat_created_at_idx');
            $table->dropIndex('issue_messages_chat_read_created_at_idx');
        });

        $legacySenderValues = [
            IssueMessageSenderType::User->value,
            IssueMessageSenderType::Officer->value,
        ];
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $quoted = "'".implode("','", $legacySenderValues)."'";

            DB::statement(
                "ALTER TABLE issue_messages MODIFY sender_type ENUM({$quoted}) NOT NULL"
            );
        } else {
            Schema::table('issue_messages', function (Blueprint $table) use ($legacySenderValues): void {
                $table->enum('sender_type', $legacySenderValues)->change();
            });
        }

        Schema::table('issue_messages', function (Blueprint $table): void {
            $table->text('content')->nullable(false)->change();
            $table->dropConstrainedForeignId('issue_chat_id');
            $table->dropColumn(['message_type', 'meta']);
        });
    }
};
