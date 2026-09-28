<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Language::query()->where('is_default', true)->update(['is_default' => false]);

        Language::updateOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'native_name' => 'English', 'is_default' => true, 'is_active' => true],
        );
    }
}
