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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('assigned_officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('duplicate_of_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('neighborhood', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('status', 20)->default('open');
            $table->string('priority', 10)->default('laag');
            $table->string('department', 20);
            $table->integer('duplicate_count')->default(0);
            $table->integer('participant_count')->default(0);
            $table->integer('vote_count')->default(0);
            $table->boolean('is_flagged')->default(false);
            $table->string('visibility', 20)->default('visible');
            $table->boolean('is_anonymous')->default(false);
            $table->string('anonymous_alias', 20)->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
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
