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
        Schema::create('domain_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('recipient_type', ActorType::recipientValues());
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('managers')->nullOnDelete();
            $table->foreignId('issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->string('type', 50);
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'recipient_type', 'is_read', 'created_at'], 'domain_notifications_user_recipient_read_created_idx');
            $table->index(['officer_id', 'recipient_type', 'is_read', 'created_at'], 'domain_notifications_officer_recipient_read_created_idx');
            $table->index(['manager_id', 'recipient_type', 'is_read', 'created_at'], 'domain_notifications_manager_recipient_read_created_idx');
            $table->index(['issue_id', 'created_at'], 'domain_notifications_issue_created_at_idx');
            $table->index(['type', 'created_at'], 'domain_notifications_type_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_notifications');
    }
};
