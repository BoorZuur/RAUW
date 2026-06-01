<?php

use App\Enums\ReportPeriod;
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
        Schema::create('report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_by_manager_id')->nullable()->constrained('managers')->nullOnDelete();
            $table->enum('period', ReportPeriod::values());
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_issues')->default(0);
            $table->integer('open_issues')->default(0);
            $table->integer('resolved_issues')->default(0);
            $table->decimal('avg_resolution_days', 5, 2)->nullable();
            $table->integer('count_laag')->default(0);
            $table->integer('count_midden')->default(0);
            $table->integer('count_zwaar')->default(0);
            $table->integer('count_wijkbeheer')->default(0);
            $table->integer('count_boa_jeugd')->default(0);
            $table->decimal('satisfaction_rate', 5, 2)->nullable();
            $table->dateTime('generated_at')->useCurrent();

            $table->unique(['period', 'period_start']);
            $table->index('generated_by_manager_id', 'report_snapshots_generated_by_manager_id_idx');
            $table->index('generated_at', 'report_snapshots_generated_at_idx');
            $table->index(['period', 'generated_at'], 'report_snapshots_period_generated_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
    }
};
