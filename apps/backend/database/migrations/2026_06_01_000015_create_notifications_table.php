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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_type', 10);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('managers')->nullOnDelete();
            $table->foreignId('issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->string('type', 50);
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
