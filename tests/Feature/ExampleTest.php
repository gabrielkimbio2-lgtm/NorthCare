<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Institution;
use App\Models\PractitionerProfile;
use App\Models\Region;
use App\Models\Service;
use App\Models\User;
use App\UserRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_page_uses_catalog_options_and_hides_unapproved_profiles(): void
    {
        $this->seed(DatabaseSeeder::class);
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Find the right care')
            ->assertSee('Nicosia')
            ->assertSee('Doctor / Dentist')
            ->assertSee('No profiles match those filters')
            ->assertDontSee('Dr. Aylin Demir');
    }

    public function test_directory_filters_profiles_by_search_city_and_specialty(): void
    {
        $this->seed(DatabaseSeeder::class);
        $city = City::query()->where('name', 'Famagusta')->firstOrFail();
        $region = Region::query()->where('city_id', $city->id)->where('name', 'Old Town')->firstOrFail();
        $category = Category::query()->where('name', 'Dietitian')->firstOrFail();
        $profile = PractitionerProfile::create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'name' => 'Dr. Sofia Example',
            'is_published' => true,
            'visibility' => ['name' => 'public', 'category' => 'public', 'city' => 'public', 'region' => 'public'],
        ]);
        $service = Service::query()->where('name', 'Nutrition consultation')->firstOrFail();
        $profile->services()->attach($service->id);

        $response = $this->get('/?type=practitioner&search=nutrition&city='.$city->id.'&region='.$region->id.'&specialty='.$category->id.'&service='.$service->id);

        $response
            ->assertOk()
            ->assertSee('Dr. Sofia Example')
            ->assertSee('Nutrition consultation');
    }

    public function test_public_directory_searches_institutions_by_type_city_region_specialty_and_name(): void
    {
        $this->seed(DatabaseSeeder::class);
        $city = City::query()->where('name', 'Kyrenia')->firstOrFail();
        $region = Region::query()->where('city_id', $city->id)->where('name', 'Lapta')->firstOrFail();
        $category = Category::query()->where('kind', 'hospital')->where('name', 'Eye care')->firstOrFail();
        $institution = Institution::create([
            'name' => 'North Coast Eye Hospital',
            'description' => 'Regional eye care',
            'visibility' => ['name' => 'public', 'description' => 'public'],
            'is_published' => true,
        ]);
        $institution->categories()->attach($category->id);
        $institution->locations()->create([
            'city_id' => $city->id,
            'region_id' => $region->id,
            'name' => 'Lapta Branch',
            'address' => 'Coastal Road',
            'visibility' => ['address' => 'public'],
            'is_published' => true,
        ]);

        $privateInstitution = Institution::create([
            'name' => 'Private Clinic',
            'visibility' => ['name' => 'public'],
            'is_published' => true,
        ]);
        $privateInstitution->categories()->attach($category->id);
        $privateInstitution->locations()->create([
            'city_id' => $city->id,
            'region_id' => $region->id,
            'address' => 'Private Road',
            'visibility' => ['address' => 'registered'],
            'is_published' => true,
        ]);

        $response = $this->get('/?type=institution&city='.$city->id.'&region='.$region->id.'&specialty='.$category->id.'&search=North+Coast');

        $response
            ->assertOk()
            ->assertSee('North Coast Eye Hospital')
            ->assertSee('Lapta Branch')
            ->assertDontSee('Private Clinic');
    }

    public function test_new_accounts_default_to_active_patients(): void
    {
        $user = User::factory()->create();
        $user->refresh();

        $this->assertSame(UserRole::Patient, $user->role);
        $this->assertSame('active', $user->account_status);
    }

    public function test_one_practitioner_profile_and_multiple_institution_locations_are_supported(): void
    {
        $city = City::create(['name' => 'Nicosia', 'slug' => 'nicosia']);
        $region = $city->regions()->create(['name' => 'Central', 'slug' => 'central']);
        $category = Category::create(['kind' => 'doctor', 'name' => 'Dentist', 'slug' => 'dentist']);

        $practitioner = User::factory()->create();
        $practitionerProfile = $practitioner->practitionerProfile()->create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'name' => 'Dr. Ada Example',
        ]);

        $institutionUser = User::factory()->create();
        $institution = $institutionUser->institution()->create(['name' => 'North Clinic']);
        $institution->locations()->create(['city_id' => $city->id, 'region_id' => $region->id, 'address' => 'First address']);
        $institution->locations()->create(['city_id' => $city->id, 'region_id' => $region->id, 'address' => 'Second address']);

        $this->assertSame($practitioner->id, $practitionerProfile->refresh()->user_id);
        $this->assertCount(2, $institution->locations()->get());
    }

    public function test_practitioner_field_visibility_distinguishes_public_and_registered_users(): void
    {
        $profile = new PractitionerProfile(['visibility' => ['phone' => 'registered']]);

        $this->assertFalse($profile->canViewField('phone', false));
        $this->assertTrue($profile->canViewField('phone', true));
    }
}
