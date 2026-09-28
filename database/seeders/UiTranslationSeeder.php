<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class UiTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $english = Language::query()->where('code', 'en')->firstOrFail();

        $translations = [
            'navigation.find_care' => 'Find care',
            'navigation.for_practitioners' => 'For practitioners',
            'navigation.sign_in' => 'Sign in',
            'navigation.join' => 'Join',
            'directory.eyebrow' => 'LOCAL CARE, MADE EASIER',
            'directory.heading_first' => 'Find the right care,',
            'directory.heading_second' => 'closer to home.',
            'directory.description' => 'Explore healthcare practitioners across Northern Cyprus by name, specialty, or location.',
            'directory.search_name' => 'Name or service',
            'directory.search_placeholder' => 'e.g. dentist, nutrition',
            'directory.city' => 'City',
            'directory.all_cities' => 'All cities',
            'directory.specialty' => 'Specialty',
            'directory.all_specialties' => 'All specialties',
            'directory.search_button' => 'Search directory',
            'directory.results_eyebrow' => 'YOUR LOCAL DIRECTORY',
            'directory.results_title' => 'Practitioners to know',
            'directory.review_badge' => 'REVIEWED',
            'directory.preview_notice' => 'Profiles are reviewed by an administrator before appearing in the directory.',
            'directory.empty_title' => 'No profiles match those filters',
            'directory.empty_description' => 'Try a different name, specialty, or city.',
            'directory.clear_search' => 'Clear search',
        ];

        foreach ($translations as $key => $value) {
            UiTranslation::updateOrCreate(
                ['language_id' => $english->id, 'key' => $key],
                ['value' => $value],
            );
        }
    }
}
