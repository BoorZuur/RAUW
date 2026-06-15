<?php

use App\Enums\ChatStatus;
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
        Schema::create('issue_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ChatStatus::values())->default(ChatStatus::Closed->value);
            $table->foreignId('opened_by_officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->foreignId('closed_by_officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['issue_id', 'user_id'], 'issue_chats_issue_user_unique');
            $table->index(['issue_id', 'status'], 'issue_chats_issue_status_idx');
            $table->index(['user_id', 'status'], 'issue_chats_user_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_chats');
    }
};
