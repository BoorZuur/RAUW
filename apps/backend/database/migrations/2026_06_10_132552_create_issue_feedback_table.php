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
        Schema::dropIfExists('issue_resolutions');

        Schema::create('issue_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_satisfied');
            $table->text('comment')->nullable();
            $table->dateTime('submitted_at');
            $table->dateTime('updated_at')->nullable();

            $table->unique(['issue_id', 'reviewer_user_id']);
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_feedback');
        
        Schema::create('issue_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->unique()->constrained('issues')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_satisfied');
            $table->text('comment')->nullable();
            $table->dateTime('answered_at')->useCurrent();
        });
    }
};
