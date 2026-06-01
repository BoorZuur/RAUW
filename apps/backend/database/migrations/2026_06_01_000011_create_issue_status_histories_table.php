<?php

use App\Enums\IssueStatus;
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
        Schema::create('issue_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('changed_by_officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->enum('old_status', IssueStatus::values())->nullable();
            $table->enum('new_status', IssueStatus::values());
            $table->decimal('officer_lat', 10, 8)->nullable();
            $table->decimal('officer_lng', 11, 8)->nullable();
            $table->text('note')->nullable();
            $table->dateTime('changed_at')->useCurrent();

            $table->index(['issue_id', 'changed_at'], 'issue_status_histories_issue_changed_at_idx');
            $table->index(['changed_by_officer_id', 'changed_at'], 'issue_status_histories_officer_changed_at_idx');
            $table->index(['new_status', 'changed_at'], 'issue_status_histories_new_status_changed_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_status_histories');
    }
};
