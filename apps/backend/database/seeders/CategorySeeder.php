<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed stable issue category and department reference data.
     */
    public function run(): void
    {
        $departments = collect([
            'wijkbeheer' => 'Wijkbeheer',
            'boa_jeugd' => 'BOA / Jeugd',
        ])->mapWithKeys(fn (string $name, string $code): array => [
            $code => Department::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true],
            ),
        ]);

        // 'priority' orders main categories; lower number = higher priority.
        // 'departments' lists the department codes the category is assigned to.
        collect([
            ['name' => 'Afval en vervuiling', 'departments' => ['wijkbeheer'], 'priority' => 10],
            ['name' => 'Groen en openbare ruimte', 'departments' => ['wijkbeheer'], 'priority' => 20],
            ['name' => 'Straatverlichting', 'departments' => ['wijkbeheer'], 'priority' => 30],
            ['name' => 'Verkeersveiligheid', 'departments' => ['wijkbeheer'], 'priority' => 40],
            ['name' => 'Jeugdoverlast', 'departments' => ['boa_jeugd'], 'priority' => 10],
            ['name' => 'Vandalisme', 'departments' => ['boa_jeugd'], 'priority' => 20],
            ['name' => 'Geluidsoverlast', 'departments' => ['wijkbeheer', 'boa_jeugd'], 'priority' => 30],
        ])->each(function (array $data) use ($departments): void {
            $category = Category::firstOrCreate(
                ['name' => $data['name']],
                [
                    'priority' => $data['priority'],
                    'weight' => null,
                    'parent_id' => null,
                    'is_active' => true,
                ],
            );

            $category->departments()->syncWithoutDetaching(
                collect($data['departments'])
                    ->map(fn (string $code): int => $departments[$code]->id)
                    ->all()
            );
        });
    }
}
