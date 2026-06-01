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
        Schema::create('content_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained('issue_comments')->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('issue_messages')->nullOnDelete();
            $table->foreignId('flagged_by_officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->foreignId('reviewed_by_manager_id')->nullable()->constrained('managers')->nullOnDelete();
            $table->string('flag_source', 10);
            $table->string('flag_reason', 25);
            $table->string('matched_keyword', 100)->nullable();
            $table->boolean('counts_toward_review')->default(false);
            $table->string('action_taken', 50)->nullable();
            $table->dateTime('flagged_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_flags');
    }
};
