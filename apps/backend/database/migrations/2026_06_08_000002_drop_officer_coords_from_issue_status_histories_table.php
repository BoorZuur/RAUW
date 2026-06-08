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
        Schema::table('issue_status_histories', function (Blueprint $table) {
            $table->dropColumn(['officer_lat', 'officer_lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_status_histories', function (Blueprint $table) {
            $table->decimal('officer_lat', 10, 8)->nullable();
            $table->decimal('officer_lng', 11, 8)->nullable();
        });
    }
};
