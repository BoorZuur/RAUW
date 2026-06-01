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
        Schema::create('flagged_content_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained('issue_comments')->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('issue_messages')->nullOnDelete();
            $table->foreignId('reviewed_by_manager_id')->nullable()->constrained('managers')->nullOnDelete();
            $table->string('matched_keyword', 100);
            $table->string('action_taken', 50)->nullable();
            $table->dateTime('flagged_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flagged_content_log');
    }
};
