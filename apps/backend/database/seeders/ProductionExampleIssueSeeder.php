<?php

namespace Database\Seeders;

use App\Actions\Issues\CreateIssue;
use App\Models\Category;
use App\Models\District;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds one reporter user and a realistic example issue in the Centrum wijk.
 *
 * Issues require a citizen owner; this seeder creates that reporter account.
 * Re-runs are idempotent: the example issue is created only once.
 */
class ProductionExampleIssueSeeder extends Seeder
{
    private const EXAMPLE_ISSUE_TITLE = 'Scooter geparkeerd op het trottoir bij school';

    private const DEFAULT_REPORTER_EMAIL = 'reporter@example.com';

    private const DEFAULT_REPORTER_USERNAME = 'reporter';

    public function run(): void
    {
        if (Issue::query()->where('title', self::EXAMPLE_ISSUE_TITLE)->exists()) {
            return;
        }

        $district = District::query()
            ->where('name', 'Centrum')
            ->first();

        if ($district === null) {
            throw new \RuntimeException('Centrum district not found. Run DistrictSeeder first.');
        }

        $category = Category::query()
            ->where('name', 'Parkeren op stoep')
            ->first();

        if ($category === null) {
            throw new \RuntimeException('Parkeren op stoep category not found. Run CategorySeeder first.');
        }

        $email = (string) env('SEED_REPORTER_EMAIL', self::DEFAULT_REPORTER_EMAIL);
        $username = (string) env('SEED_REPORTER_USERNAME', self::DEFAULT_REPORTER_USERNAME);
        $password = $this->requireEnvPassword('SEED_REPORTER_PASSWORD');

        $reporter = User::updateOrCreate(
            ['email' => $email],
            [
                'username' => $username,
                'password' => $password,
                'is_active' => true,
            ],
        );

        (new CreateIssue)->create($reporter, [
            'title' => self::EXAMPLE_ISSUE_TITLE,
            'content' => 'Sinds drie dagen staat er een scooter op het trottoir ter hoogte van Westewagenstraat 29, vlak bij basisschool De Regenboog. Ouders met kinderwagens en rolstoelgebruikers moeten nu de rijbaan op. Graag controleren en zo nodig verwijderen.',
            'category_id' => $category->id,
            'district_id' => $district->id,
            'postal_code' => '3011 AN',
            'address' => 'Westewagenstraat 29, Rotterdam',
            'latitude' => 51.92204,
            'longitude' => 4.48052,
            'is_anonymous' => false,
        ]);
    }

    private function requireEnvPassword(string $key): string
    {
        $password = (string) env($key, '');

        if ($password === '') {
            throw new \RuntimeException("{$key} must be set when running ProductionSeeder.");
        }

        return $password;
    }
}
