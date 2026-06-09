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
        Schema::create('hubs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('address', 255);
            $table->string('postal_code', 10);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->boolean('is_active')->default(false);
            $table->unsignedSmallInteger('radius_meters')->default(100);
            $table->timestamps();
        });

        Schema::table('officer_sessions', function (Blueprint $table) {
            $table->foreign('hub_id')
                ->references('id')
                ->on('hubs')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('officer_sessions', function (Blueprint $table) {
            $table->dropForeign(['hub_id']);
        });

        Schema::dropIfExists('hubs');
    }
};
