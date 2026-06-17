<?php

use App\Enums\ActorType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('issue_comments', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false)->after('content');
        });

        DB::table('issue_comments')
            ->join('issues', 'issue_comments.issue_id', '=', 'issues.id')
            ->where('issue_comments.author_type', ActorType::User->value)
            ->whereColumn('issue_comments.user_id', 'issues.user_id')
            ->where('issues.is_anonymous', true)
            ->update(['issue_comments.is_anonymous' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_comments', function (Blueprint $table) {
            $table->dropColumn('is_anonymous');
        });
    }
};
