<?php

namespace Database\Seeders;

use App\Models\BlockedKeyword;
use Illuminate\Database\Seeder;

class BlockedKeywordSeeder extends Seeder
{
    /**
     * Seed baseline moderation keywords for local and development environments.
     */
    public function run(): void
    {
        collect([
            'spam',
            'scheldwoord',
            'bedreiging',
        ])->each(fn (string $keyword): BlockedKeyword => BlockedKeyword::updateOrCreate(
            ['keyword_normalized' => BlockedKeyword::normalizeKeyword($keyword)],
            ['keyword' => $keyword, 'created_at' => now()]
        ));
    }
}
