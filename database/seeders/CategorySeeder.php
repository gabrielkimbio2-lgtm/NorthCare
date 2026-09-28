<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryGroups = [
            'doctor' => ['Doctor', ['Dentist', 'Surgeon', 'Chiropractor']],
            'specialist' => ['Specialist', ['Psychologist', 'Dietitian']],
            'hospital' => ['Hospital', ['Eye care', 'General hospital']],
        ];

        foreach ($categoryGroups as $kind => [$rootName, $children]) {
            $root = Category::updateOrCreate(
                ['parent_id' => null, 'slug' => str($rootName)->slug()->toString()],
                ['kind' => $kind, 'name' => $rootName, 'is_active' => true],
            );

            foreach ($children as $childName) {
                Category::updateOrCreate(
                    ['parent_id' => $root->id, 'slug' => str($childName)->slug()->toString()],
                    ['kind' => $kind, 'name' => $childName, 'is_active' => true],
                );
            }
        }
    }
}
