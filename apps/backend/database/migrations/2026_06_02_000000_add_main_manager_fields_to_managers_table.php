<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds explicit manager-hierarchy fields so the backend can identify the
     * single main manager and link every created manager back to its creator.
     *
     * NOTE: The initial main manager MUST be provisioned through trusted
     * operational seeding or direct administration (see AuthDemoAccountsSeeder
     * for local development). The public API must never set `is_main_manager`.
     */
    public function up(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->boolean('is_main_manager')
                ->default(false)
                ->index()
                ->after('is_active');

            $table->foreignId('created_by_manager_id')
                ->nullable()
                ->after('is_main_manager')
                ->constrained('managers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('managers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_manager_id');
            $table->dropIndex(['is_main_manager']);
            $table->dropColumn('is_main_manager');
        });
    }
};
