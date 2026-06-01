<?php

use App\Enums\JoinedVia;
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
        Schema::create('issue_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('joined_via', JoinedVia::values())->default(JoinedVia::Manual->value);
            $table->foreignId('via_issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->dateTime('joined_at')->useCurrent();

            $table->unique(['issue_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_participants');
    }
};
