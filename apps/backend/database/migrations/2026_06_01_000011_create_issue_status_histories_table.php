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
        Schema::create('issue_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('changed_by_officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->decimal('officer_lat', 10, 8)->nullable();
            $table->decimal('officer_lng', 11, 8)->nullable();
            $table->text('note')->nullable();
            $table->dateTime('changed_at')->useCurrent();
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
