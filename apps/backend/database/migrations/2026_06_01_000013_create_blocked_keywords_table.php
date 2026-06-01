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
        Schema::create('blocked_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 100);
            $table->string('keyword_normalized', 100)->unique('blocked_keywords_keyword_normalized_unique');
            $table->foreignId('added_by_manager_id')->nullable()->constrained('managers')->nullOnDelete();
            $table->dateTime('created_at')->useCurrent();

            $table->index('added_by_manager_id', 'blocked_keywords_added_by_manager_id_idx');
            $table->index('created_at', 'blocked_keywords_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_keywords');
    }
};
