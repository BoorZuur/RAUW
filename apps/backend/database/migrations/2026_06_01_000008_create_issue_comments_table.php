<?php

use App\Enums\ActorType;
use App\Enums\Visibility;
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
        Schema::create('issue_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->enum('author_type', ActorType::issueParticipantValues());
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->text('content');
            $table->boolean('is_flagged')->default(false);
            $table->enum('visibility', Visibility::values())->default(Visibility::Visible->value);
            $table->timestamps();

            $table->index(['issue_id', 'created_at'], 'issue_comments_issue_created_at_idx');
            $table->index(['visibility', 'created_at'], 'issue_comments_visibility_created_at_idx');
            $table->index(['is_flagged', 'created_at'], 'issue_comments_flagged_created_at_idx');
            $table->index(['user_id', 'created_at'], 'issue_comments_user_created_at_idx');
            $table->index(['officer_id', 'created_at'], 'issue_comments_officer_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_comments');
    }
};
