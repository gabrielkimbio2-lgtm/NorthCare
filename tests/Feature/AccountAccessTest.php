<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Institution;
use App\Models\InstitutionLocation;
use App\Models\Language;
use App\Models\PractitionerProfile;
use App\Models\ProfileApplication;
use App\Models\Region;
use App\Models\Service;
use App\Models\ServiceSuggestion;
use App\Models\UiTranslation;
use App\Models\User;
use App\UserRole;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_register_without_admin_approval(): void
    {
        Language::create([
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'is_default' => true,
        ]);

        $response = $this->post(route('register'), [
            'account_type' => 'patient',
            'name' => 'Pat Example',
            'email' => 'patient@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'preferred_locale' => 'en',
        ]);

        $user = User::query()->where('email', 'patient@example.test')->firstOrFail();

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::Patient, $user->role);
        $this->assertSame('active', $user->account_status);
        $this->assertDatabaseMissing('profile_applications', ['applicant_user_id' => $user->id]);
    }

    public function test_patient_registration_form_hides_provider_only_fields_by_default(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->get(route('register'));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/<div class="provider-fields" id="provider-fields"\s+hidden\s*>/',
            $response->getContent(),
        );
    }

    public function test_practitioner_registration_creates_pending_application_and_private_document(): void
    {
        Storage::fake('local');
        Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true]);
        [$category, $city, $region] = $this->createPractitionerCatalog();

        $response = $this->post(route('register'), [
            'account_type' => 'practitioner',
            'name' => 'Dr. Ada Example',
            'email' => 'doctor@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'preferred_locale' => 'en',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'phone' => '+90 392 000 0000',
            'documents' => [UploadedFile::fake()->create('license.pdf', 100, 'application/pdf')],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('account.pending-approval'));
        $user = User::query()->where('email', 'doctor@example.test')->firstOrFail();
        $application = ProfileApplication::query()->whereBelongsTo($user, 'applicant')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->get(route('account.pending-approval'))->assertSee('Your profile is awaiting approval');
        $this->assertSame(UserRole::Practitioner, $user->role);
        $this->assertSame('pending', $user->account_status);
        $this->assertSame('pending', $application->status);
        $this->assertDatabaseHas('application_documents', ['profile_application_id' => $application->id]);
        Storage::disk('local')->assertExists($application->documents()->value('storage_path'));
    }

    public function test_pending_practitioner_and_institution_logins_go_to_approval_status_only(): void
    {
        foreach ([UserRole::Practitioner, UserRole::Institution] as $role) {
            $user = User::factory()->create();
            $user->role = $role;
            $user->account_status = 'pending';
            $user->save();
            $user->refresh();

            $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('account.pending-approval'));
            $this->assertAuthenticatedAs($user);
            $this->get(route('account.pending-approval'))->assertSee('Your profile is awaiting approval');
            $this->get(route('dashboard'))->assertRedirect(route('account.pending-approval'));

            if ($role === UserRole::Practitioner) {
                $this->get(route('practitioner.profile.edit'))->assertForbidden();
            } else {
                $this->get(route('institution.profile.edit'))->assertForbidden();
            }

            $this->post(route('logout'))->assertRedirect(route('directory.index'));
        }
    }

    public function test_approved_practitioner_login_goes_directly_to_practitioner_profile(): void
    {
        [$category, $city] = $this->createPractitionerCatalog();
        $practitioner = User::factory()->create();
        $practitioner->role = UserRole::Practitioner;
        $practitioner->account_status = 'active';
        $practitioner->save();
        $practitioner->practitionerProfile()->create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'name' => 'Dr. Approved Example',
            'is_published' => true,
        ]);

        $this->post(route('login'), ['email' => $practitioner->email, 'password' => 'password'])
            ->assertRedirect(route('practitioner.profile.edit'));

        $this->get(route('practitioner.profile.edit'))->assertOk();
    }

    public function test_approved_institution_login_goes_directly_to_institution_profile(): void
    {
        $institutionOwner = User::factory()->create();
        $institutionOwner->role = UserRole::Institution;
        $institutionOwner->account_status = 'active';
        $institutionOwner->save();
        $institutionOwner->institution()->create(['name' => 'Approved Example Institution', 'is_published' => true]);

        $this->post(route('login'), ['email' => $institutionOwner->email, 'password' => 'password'])
            ->assertRedirect(route('institution.profile.edit'));

        $this->get(route('institution.profile.edit'))->assertOk();
    }

    public function test_public_registration_rejects_the_admin_role(): void
    {
        $response = $this->post(route('register'), [
            'account_type' => 'admin',
            'name' => 'Untrusted Admin',
            'email' => 'admin@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response->assertSessionHasErrors('account_type');
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }

    public function test_existing_admin_can_create_another_admin(): void
    {
        $admin = $this->createAdministrator();

        $this->from(route('admin.dashboard'))
            ->actingAs($admin)
            ->post(route('admin.administrators.store'), [
                'name' => 'Second Admin',
                'email' => 'second-admin@example.test',
                'password' => 'long-enough-admin-password',
                'password_confirmation' => 'long-enough-admin-password',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $newAdministrator = User::query()->where('email', 'second-admin@example.test')->firstOrFail();
        $this->assertSame(UserRole::Admin, $newAdministrator->role);
        $this->assertSame('active', $newAdministrator->account_status);
    }

    public function test_admin_must_use_the_separate_sign_in(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->role = UserRole::Admin;
        $admin->save();

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_seeder_repairs_an_existing_account_and_allows_admin_login(): void
    {
        config(['auth.initial_admin' => [
            'name' => 'System Administrator',
            'email' => 'admin@cypruscare.com',
            'password' => null,
        ]]);
        $administrator = User::factory()->create(['email' => 'admin@cypruscare.com']);
        $administrator->role = UserRole::Patient;
        $administrator->account_status = 'active';
        $administrator->save();

        $this->seed(AdminSeeder::class);

        $administrator->refresh();
        $this->assertSame(UserRole::Admin, $administrator->role);
        $this->assertSame('active', $administrator->account_status);

        $this->post(route('admin.login'), [
            'email' => 'admin@cypruscare.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($administrator);
    }

    public function test_admin_seeder_creates_first_admin_from_configured_credentials(): void
    {
        config(['auth.initial_admin' => [
            'name' => 'First System Owner',
            'email' => 'system-owner@example.test',
            'password' => 'configured-system-password',
        ]]);

        $this->seed(AdminSeeder::class);

        $administrator = User::query()->where('email', 'system-owner@example.test')->firstOrFail();
        $this->assertSame(UserRole::Admin, $administrator->role);
        $this->assertSame('active', $administrator->account_status);

        $this->post(route('admin.login'), [
            'email' => 'system-owner@example.test',
            'password' => 'configured-system-password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_patient_cannot_open_admin_dashboard(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($patient)
            ->get(route('admin.applications.index'))
            ->assertForbidden();
    }

    public function test_admin_can_edit_and_match_a_practitioner_profile_before_approval(): void
    {
        $admin = $this->createAdministrator();
        [$category, $city, $region] = $this->createPractitionerCatalog();
        $applicant = $this->createPendingApplicant(UserRole::Practitioner);
        $profile = PractitionerProfile::create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'name' => 'Existing Directory Name',
        ]);
        $application = $this->createPendingApplication($applicant, UserRole::Practitioner, $category, $city, $region);

        $this->actingAs($admin)
            ->patch(route('admin.applications.update', $application), [
                'decision' => 'approve',
                'category_id' => $category->id,
                'city_id' => $city->id,
                'region_id' => $region->id,
                'name' => 'Dr. Applicant Name',
                'contact_email' => 'public@example.test',
                'phone' => '+90 392 000 0000',
                'address' => 'Central, Nicosia',
                'description' => 'Reviewed practitioner profile',
                'visibility' => ['name' => 'public', 'phone' => 'registered'],
                'matched_practitioner_profile_id' => $profile->id,
            ])
            ->assertRedirect(route('admin.applications.index'));

        $profile->refresh();
        $application->refresh();

        $this->assertSame($applicant->id, $profile->user_id);
        $this->assertSame('Dr. Applicant Name', $profile->name);
        $this->assertTrue($profile->is_published);
        $this->assertSame('approved', $application->status);
        $this->assertSame($profile->id, $application->matched_practitioner_profile_id);
        $this->assertSame('active', $applicant->fresh()->account_status);
        $this->assertDatabaseCount('practitioner_profiles', 1);
    }

    public function test_admin_can_approve_institution_application_and_create_its_first_location(): void
    {
        $admin = $this->createAdministrator();
        $hospital = Category::create(['kind' => 'hospital', 'name' => 'Hospital', 'slug' => 'hospital']);
        $category = $hospital->children()->create(['kind' => 'hospital', 'name' => 'Eye Centre', 'slug' => 'eye-centre']);
        $city = City::create(['name' => 'Famagusta', 'slug' => 'famagusta']);
        $region = $city->regions()->create(['name' => 'Old Town', 'slug' => 'old-town']);
        $applicant = $this->createPendingApplicant(UserRole::Institution);
        $application = $this->createPendingApplication($applicant, UserRole::Institution, $category, $city, $region);

        $this->actingAs($admin)
            ->patch(route('admin.applications.update', $application), [
                'decision' => 'approve',
                'category_id' => $category->id,
                'city_id' => $city->id,
                'region_id' => $region->id,
                'name' => 'East Coast Eye Hospital',
                'contact_email' => 'office@example.test',
                'phone' => '+90 392 111 1111',
                'address' => 'Old Town, Famagusta',
                'description' => 'Eye care institution',
                'visibility' => ['name' => 'public', 'phone' => 'registered'],
            ])
            ->assertRedirect(route('admin.applications.index'));

        $institution = Institution::query()->where('user_id', $applicant->id)->firstOrFail();
        $location = InstitutionLocation::query()->where('institution_id', $institution->id)->firstOrFail();

        $this->assertSame('East Coast Eye Hospital', $institution->name);
        $this->assertSame($city->id, $location->city_id);
        $this->assertTrue($institution->is_published);
        $this->assertTrue($location->is_published);
        $this->assertTrue($institution->categories()->whereKey($category->id)->exists());
        $this->assertSame('active', $applicant->fresh()->account_status);
    }

    public function test_rejected_provider_application_does_not_create_a_profile(): void
    {
        $admin = $this->createAdministrator();
        [$category, $city, $region] = $this->createPractitionerCatalog();
        $applicant = $this->createPendingApplicant(UserRole::Practitioner);
        $application = $this->createPendingApplication($applicant, UserRole::Practitioner, $category, $city, $region);

        $this->actingAs($admin)
            ->patch(route('admin.applications.update', $application), [
                'decision' => 'reject',
                'category_id' => $category->id,
                'city_id' => $city->id,
                'region_id' => $region->id,
                'name' => $application->name,
                'visibility' => ['name' => 'public'],
            ])
            ->assertRedirect(route('admin.applications.index'));

        $this->assertSame('rejected', $application->fresh()->status);
        $this->assertSame('rejected', $applicant->fresh()->account_status);
        $this->assertDatabaseCount('practitioner_profiles', 0);
    }

    public function test_admin_can_add_location_and_two_level_category_catalog_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->createAdministrator();

        $this->actingAs($admin)
            ->get(route('admin.catalog.index'))
            ->assertOk()
            ->assertSee('Cities and regions');

        $this->from(route('admin.catalog.index'))
            ->actingAs($admin)
            ->post(route('admin.cities.store'), ['name' => 'Lefka'])
            ->assertRedirect(route('admin.catalog.index'));

        $city = City::query()->where('name', 'Lefka')->firstOrFail();

        $this->from(route('admin.catalog.index'))
            ->actingAs($admin)
            ->post(route('admin.regions.store'), ['city_id' => $city->id, 'name' => 'West'])
            ->assertRedirect(route('admin.catalog.index'));

        $this->from(route('admin.catalog.index'))
            ->actingAs($admin)
            ->post(route('admin.categories.store'), ['kind' => 'specialist', 'name' => 'Therapist'])
            ->assertRedirect(route('admin.catalog.index'));

        $root = Category::query()->where('name', 'Therapist')->firstOrFail();

        $this->from(route('admin.catalog.index'))
            ->actingAs($admin)
            ->post(route('admin.categories.store'), ['parent_id' => $root->id, 'name' => 'Speech therapy'])
            ->assertRedirect(route('admin.catalog.index'));

        $child = Category::query()->where('name', 'Speech therapy')->firstOrFail();

        $this->from(route('admin.catalog.index'))
            ->actingAs($admin)
            ->post(route('admin.categories.store'), ['parent_id' => $child->id, 'name' => 'Child category'])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseHas('regions', ['city_id' => $city->id, 'name' => 'West']);
        $this->assertDatabaseMissing('categories', ['name' => 'Child category']);
    }

    public function test_admin_can_add_a_language_and_visitors_can_switch_to_its_database_translations(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->createAdministrator();
        $translations = UiTranslation::query()->pluck('value', 'key')->all();
        $translations['directory.heading_first'] = 'Encuentra atención';

        $this->from(route('admin.languages.index'))
            ->actingAs($admin)
            ->post(route('admin.languages.store'), [
                'code' => 'es',
                'name' => 'Spanish',
                'native_name' => 'Español',
                'translations' => $translations,
            ])
            ->assertRedirect(route('admin.languages.index'));

        $this->assertDatabaseHas('languages', ['code' => 'es', 'is_active' => true]);

        $this->from(route('directory.index'))
            ->post(route('language.switch'), ['locale' => 'es'])
            ->assertRedirect(route('directory.index'));

        $this->get(route('directory.index'))->assertSee('Encuentra atención');
    }

    public function test_practitioner_can_select_services_and_submit_a_suggestion_for_admin_review(): void
    {
        $this->seed(DatabaseSeeder::class);
        $category = Category::query()->where('name', 'Dentist')->firstOrFail();
        $city = City::query()->where('name', 'Nicosia')->firstOrFail();
        $practitioner = User::factory()->create();
        $practitioner->role = UserRole::Practitioner;
        $practitioner->account_status = 'active';
        $practitioner->save();
        $practitioner->refresh();
        $profile = $practitioner->practitionerProfile()->create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'name' => 'Dr. Practice Example',
            'is_published' => true,
        ]);
        $service = Service::query()->where('name', 'Dental examination')->firstOrFail();

        $this->from(route('practitioner.profile.edit'))
            ->actingAs($practitioner)
            ->put(route('practitioner.profile.services.update'), [
                'services' => [$service->id],
                'service_durations' => [$service->id => 30],
            ])
            ->assertRedirect(route('practitioner.profile.edit'));

        $this->from(route('practitioner.profile.edit'))
            ->actingAs($practitioner)
            ->post(route('practitioner.service-suggestions.store'), ['name' => 'Jaw mobility session'])
            ->assertRedirect(route('practitioner.profile.edit'));

        $suggestion = ServiceSuggestion::query()->where('submitted_by', $practitioner->id)->firstOrFail();
        $admin = $this->createAdministrator();

        $this->from(route('admin.catalog.index'))
            ->actingAs($admin)
            ->patch(route('admin.service-suggestions.update', $suggestion), [
                'decision' => 'approve',
                'name' => 'Jaw mobility session',
            ])
            ->assertRedirect(route('admin.catalog.index'));

        $this->assertDatabaseHas('practitioner_profile_service', [
            'practitioner_profile_id' => $profile->id,
            'service_id' => $service->id,
        ]);
        $this->assertSame('approved', $suggestion->fresh()->status);
        $this->assertDatabaseHas('services', ['name' => 'Jaw mobility session', 'is_active' => true]);
    }

    public function test_admin_can_create_provider_profiles_and_multiple_institution_locations(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->createAdministrator();
        $city = City::query()->where('name', 'Nicosia')->firstOrFail();
        $secondCity = City::query()->where('name', 'Kyrenia')->firstOrFail();
        $dentist = Category::query()->where('name', 'Dentist')->firstOrFail();
        $eyeCare = Category::query()->where('name', 'Eye care')->firstOrFail();
        $service = Service::query()->where('name', 'Dental examination')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.profiles.create'))
            ->assertOk()
            ->assertSee('Practitioner profile');

        $this->from(route('admin.profiles.create'))
            ->actingAs($admin)
            ->post(route('admin.practitioners.store'), [
                'name' => 'Dr. Direct Entry',
                'category_id' => $dentist->id,
                'city_id' => $city->id,
                'service_ids' => [$service->id],
                'visibility' => ['name' => 'public', 'phone' => 'registered'],
            ])
            ->assertRedirect(route('admin.profiles.create'));

        $profile = PractitionerProfile::query()->where('name', 'Dr. Direct Entry')->firstOrFail();
        $this->assertNull($profile->user_id);
        $this->assertTrue($profile->is_published);
        $this->assertTrue($profile->services()->whereKey($service->id)->exists());

        $this->from(route('admin.profiles.create'))
            ->actingAs($admin)
            ->post(route('admin.institutions.store'), [
                'name' => 'Northern Eye Institute',
                'category_ids' => [$eyeCare->id],
                'city_id' => $city->id,
                'location_name' => 'Nicosia branch',
                'address' => 'Central Nicosia',
                'visibility' => ['name' => 'public', 'phone' => 'registered'],
            ])
            ->assertRedirect(route('admin.profiles.create'));

        $institution = Institution::query()->where('name', 'Northern Eye Institute')->firstOrFail();

        $this->from(route('admin.profiles.create'))
            ->actingAs($admin)
            ->post(route('admin.institutions.locations.store', $institution), [
                'city_id' => $secondCity->id,
                'name' => 'Kyrenia branch',
                'address' => 'Harbour Road',
            ])
            ->assertRedirect(route('admin.profiles.create'));

        $this->assertCount(2, $institution->locations()->get());
    }

    public function test_institution_owner_can_update_visibility_but_not_another_institutions_location(): void
    {
        $this->seed(DatabaseSeeder::class);
        $city = City::query()->where('name', 'Nicosia')->firstOrFail();
        $region = $city->regions()->firstOrFail();
        $owner = User::factory()->create();
        $owner->role = UserRole::Institution;
        $owner->account_status = 'active';
        $owner->save();
        $owner->refresh();
        $institution = $owner->institution()->create([
            'name' => 'Owner Clinic',
            'visibility' => ['name' => 'public', 'phone' => 'registered'],
        ]);
        $location = $institution->locations()->create([
            'city_id' => $city->id,
            'region_id' => $region->id,
            'address' => 'First address',
            'visibility' => ['address' => 'registered'],
        ]);

        $otherOwner = User::factory()->create();
        $otherOwner->role = UserRole::Institution;
        $otherOwner->account_status = 'active';
        $otherOwner->save();
        $otherInstitution = $otherOwner->institution()->create(['name' => 'Other Clinic']);
        $otherLocation = $otherInstitution->locations()->create(['city_id' => $city->id, 'address' => 'Other address']);

        $this->actingAs($owner)->get(route('institution.profile.edit'))->assertOk();

        $this->from(route('institution.profile.edit'))
            ->actingAs($owner)
            ->patch(route('institution.profile.update'), [
                'name' => 'Owner Clinic Updated',
                'contact_email' => 'owner@example.test',
                'phone' => '+90 392 222 2222',
                'description' => 'Updated profile',
                'visibility' => ['name' => 'public', 'contact_email' => 'registered', 'phone' => 'private', 'description' => 'public'],
            ])
            ->assertRedirect(route('institution.profile.edit'));

        $this->from(route('institution.profile.edit'))
            ->actingAs($owner)
            ->patch(route('institution.locations.update', $location), [
                'city_id' => $city->id,
                'region_id' => $region->id,
                'address' => 'Updated address',
                'contact_email' => 'branch@example.test',
                'phone' => '+90 392 333 3333',
                'visibility' => ['name' => 'public', 'contact_email' => 'private', 'phone' => 'registered', 'address' => 'registered'],
            ])
            ->assertRedirect(route('institution.profile.edit'));

        $this->assertSame('private', $institution->fresh()->visibility['phone']);
        $this->assertSame('registered', $location->fresh()->visibility['address']);

        $this->actingAs($owner)
            ->patch(route('institution.locations.update', $otherLocation), [
                'city_id' => $city->id,
                'address' => 'Not allowed',
                'visibility' => ['name' => 'public', 'contact_email' => 'private', 'phone' => 'private', 'address' => 'private'],
            ])
            ->assertNotFound();
    }

    /**
     * @return array{0: Category, 1: City, 2: Region}
     */
    private function createPractitionerCatalog(): array
    {
        $rootCategory = Category::create(['kind' => 'doctor', 'name' => 'Doctor', 'slug' => 'doctor']);
        $category = $rootCategory->children()->create(['kind' => 'doctor', 'name' => 'Dentist', 'slug' => 'dentist']);
        $city = City::create(['name' => 'Nicosia', 'slug' => 'nicosia']);
        $region = $city->regions()->create(['name' => 'Central', 'slug' => 'central']);

        return [$category, $city, $region];
    }

    private function createAdministrator(): User
    {
        $admin = User::factory()->create();
        $admin->role = UserRole::Admin;
        $admin->save();

        return $admin->refresh();
    }

    private function createPendingApplicant(UserRole $role): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->account_status = 'pending';
        $user->save();

        return $user;
    }

    private function createPendingApplication(User $user, UserRole $role, Category $category, City $city, Region $region): ProfileApplication
    {
        return ProfileApplication::create([
            'applicant_user_id' => $user->id,
            'application_type' => $role->value,
            'status' => 'pending',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'name' => 'Applicant Submitted Name',
            'contact_email' => $user->email,
            'phone' => '+90 392 000 0000',
            'address' => 'Applicant Submitted Address',
            'description' => 'Applicant submitted description',
            'visibility' => ['name' => 'public'],
        ]);
    }
}
