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
        Schema::table('issues', function (Blueprint $table) {
            // Nullable unique: multiple NULL aliases remain allowed (MySQL/SQLite).
            // DBML [unique] note is synced in Phase 8.
            $table->unique('anonymous_alias', 'issues_anonymous_alias_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropUnique('issues_anonymous_alias_unique');
        });
    }
};
