<?php

use App\Enums\ActorType;
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
        Schema::create('issue_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->enum('sender_type', ActorType::issueParticipantValues());
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->text('content');
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at')->useCurrent();

            $table->index(['issue_id', 'created_at'], 'issue_messages_issue_created_at_idx');
            $table->index(['issue_id', 'is_read', 'created_at'], 'issue_messages_issue_read_created_at_idx');
            $table->index(['is_flagged', 'created_at'], 'issue_messages_flagged_created_at_idx');
            $table->index(['user_id', 'created_at'], 'issue_messages_user_created_at_idx');
            $table->index(['officer_id', 'created_at'], 'issue_messages_officer_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_messages');
    }
};
