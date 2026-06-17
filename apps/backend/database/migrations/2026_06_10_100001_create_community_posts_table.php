<?php

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
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->constrained('officers')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->string('title', 255);
            $table->text('content');
            $table->enum('visibility', Visibility::values())->default(Visibility::Visible->value);
            $table->timestamps();

            $table->index('district_id');
            $table->index('officer_id');
            $table->index('created_at');
            $table->index(['visibility', 'created_at']);
            $table->index(['district_id', 'created_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_posts');
    }
};
