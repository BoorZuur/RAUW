<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('issues', 'chat_status') && ! Schema::hasColumn('issues', 'chat_closed_by_officer_id')) {
            return;
        }

        Schema::table('issues', function (Blueprint $table): void {
            if (Schema::hasColumn('issues', 'chat_closed_by_officer_id')) {
                $table->dropForeign(['chat_closed_by_officer_id']);
                $table->dropIndex('issues_chat_closed_by_officer_id_idx');
                $table->dropColumn('chat_closed_by_officer_id');
            }

            if (Schema::hasColumn('issues', 'chat_status')) {
                $table->dropColumn('chat_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('issues', 'chat_status') || Schema::hasColumn('issues', 'chat_closed_by_officer_id')) {
            return;
        }

        Schema::table('issues', function (Blueprint $table): void {
            $table->foreignId('chat_closed_by_officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->enum('chat_status', \App\Enums\ChatStatus::values())->default(\App\Enums\ChatStatus::Closed->value);

            $table->index('chat_closed_by_officer_id', 'issues_chat_closed_by_officer_id_idx');
        });
    }
};
