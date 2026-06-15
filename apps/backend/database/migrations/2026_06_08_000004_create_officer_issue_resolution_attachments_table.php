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
        Schema::create('officer_issue_resolution_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_issue_resolution_id')
                ->constrained('officer_issue_resolutions')
                ->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('file_url', 500)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('file_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->dateTime('uploaded_at')->useCurrent();

            $table->index('officer_issue_resolution_id', 'officer_issue_resolution_attachments_resolution_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_issue_resolution_attachments');
    }
};
