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
        Schema::create('issue_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_message_id')
                ->constrained('issue_messages')
                ->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('file_url', 500)->nullable();
            $table->string('original_name', 255);
            $table->string('file_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->dateTime('uploaded_at')->useCurrent();

            $table->index('issue_message_id', 'issue_message_attachments_message_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_message_attachments');
    }
};
