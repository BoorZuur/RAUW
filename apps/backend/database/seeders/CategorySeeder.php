<?php

namespace Database\Seeders;

use App\Enums\Department;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed stable issue category reference data.
     */
    public function run(): void
    {
        collect([
            ['name' => 'Afval en vervuiling', 'department' => Department::DistrictManagement, 'weight' => 10],
            ['name' => 'Groen en openbare ruimte', 'department' => Department::DistrictManagement, 'weight' => 20],
            ['name' => 'Straatverlichting', 'department' => Department::DistrictManagement, 'weight' => 30],
            ['name' => 'Verkeersveiligheid', 'department' => Department::DistrictManagement, 'weight' => 40],
            ['name' => 'Jeugdoverlast', 'department' => Department::BoaYouth, 'weight' => 10],
            ['name' => 'Vandalisme', 'department' => Department::BoaYouth, 'weight' => 20],
            ['name' => 'Geluidsoverlast', 'department' => Department::BoaYouth, 'weight' => 30],
        ])->each(fn (array $category): Category => Category::firstOrCreate(
            [
                'name' => $category['name'],
                'department' => $category['department']->value,
            ],
            [
                'weight' => $category['weight'],
                'parent_id' => null,
                'is_active' => true,
            ]
        ));
    }
}
