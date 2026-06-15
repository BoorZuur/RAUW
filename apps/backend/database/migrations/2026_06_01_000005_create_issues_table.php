<?php

use App\Enums\IssueStatus;
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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('assigned_officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->foreignId('district_id')->constrained('districts')->restrictOnDelete();
            $table->foreignId('duplicate_of_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('postal_code', 10)->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status', IssueStatus::values())->default(IssueStatus::Open->value);
            $table->unsignedTinyInteger('priority')->nullable();
            $table->integer('duplicate_count')->default(0);
            $table->integer('participant_count')->default(0);
            $table->boolean('is_flagged')->default(false);
            $table->enum('visibility', Visibility::values())->default(Visibility::Visible->value);
            $table->boolean('is_anonymous')->default(false);
            $table->string('anonymous_alias', 20)->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status', 'issues_status_idx');
            $table->index('priority', 'issues_priority_idx');
            $table->index('visibility', 'issues_visibility_idx');
            $table->index('user_id', 'issues_user_id_idx');
            $table->index('district_id', 'issues_district_id_idx');
            $table->index('category_id', 'issues_category_id_idx');
            $table->index('assigned_officer_id', 'issues_assigned_officer_id_idx');
            $table->index('duplicate_of_id', 'issues_duplicate_of_id_idx');
            $table->index('created_at', 'issues_created_at_idx');
            $table->index('resolved_at', 'issues_resolved_at_idx');
            $table->index(['district_id', 'created_at', 'id'], 'issues_district_created_at_id_idx');
            $table->index(['category_id', 'created_at', 'id'], 'issues_category_created_at_id_idx');
            $table->index(['status', 'district_id', 'created_at'], 'issues_status_district_created_at_idx');
            $table->index(['status', 'assigned_officer_id', 'created_at'], 'issues_status_assigned_officer_created_at_idx');
            $table->index(['visibility', 'created_at'], 'issues_visibility_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
