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
        Schema::table('domain_notifications', function (Blueprint $table) {
            $table->string('dedup_key', 191)->nullable()->unique()->after('is_read');
            $table->foreignId('community_post_id')->nullable()->after('issue_id')->constrained('community_posts')->nullOnDelete();
            $table->json('payload')->nullable()->after('body');
            $table->string('actor_type', 10)->nullable()->after('payload');
            $table->unsignedBigInteger('actor_id')->nullable()->after('actor_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_notifications', function (Blueprint $table) {
            $table->dropForeign(['community_post_id']);
            $table->dropUnique(['dedup_key']);
            $table->dropColumn([
                'dedup_key',
                'community_post_id',
                'payload',
                'actor_type',
                'actor_id',
            ]);
        });
    }
};
