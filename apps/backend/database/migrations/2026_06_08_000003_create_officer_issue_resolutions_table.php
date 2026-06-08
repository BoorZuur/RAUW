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
        Schema::create('officer_issue_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->unique()->constrained('issues')->cascadeOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->string('title', 255);
            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_issue_resolutions');
    }
};
