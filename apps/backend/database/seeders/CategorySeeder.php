<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed BOA / Jeugd issue category reference data.
     */
    public function run(): void
    {
        Department::firstOrCreate(
            ['code' => 'wijkbeheer'],
            ['name' => 'Wijkbeheer', 'is_active' => true],
        );

        $boaJeugd = Department::firstOrCreate(
            ['code' => 'boa_jeugd'],
            ['name' => 'BOA / Jeugd', 'is_active' => true],
        );

        $data = require __DIR__.'/data/boa_jeugd_categories.php';
        $seededNames = [];

        foreach ($data['mains'] as $main) {
            $category = Category::updateOrCreate(
                ['name' => $main['name']],
                [
                    'priority' => $main['priority'],
                    'parent_id' => null,
                    'is_active' => true,
                ],
            );

            $category->departments()->sync([$boaJeugd->id]);
            $seededNames[] = $main['name'];
        }

        $mainIds = Category::query()
            ->whereIn('name', collect($data['mains'])->pluck('name'))
            ->pluck('id', 'name');

        foreach ($data['subs'] as $sub) {
            $parentId = $mainIds[$sub['parent']] ?? null;

            if ($parentId === null) {
                throw new \RuntimeException("Parent category not found for subcategory [{$sub['name']}]: {$sub['parent']}");
            }

            $category = Category::updateOrCreate(
                ['name' => $sub['name']],
                [
                    'priority' => null,
                    'parent_id' => $parentId,
                    'is_active' => true,
                ],
            );

            $category->departments()->sync([$boaJeugd->id]);
            $seededNames[] = $sub['name'];
        }

        Category::query()
            ->whereNotIn('name', $seededNames)
            ->each(function (Category $category): void {
                $category->departments()->detach();
                $category->delete();
            });
    }
}
