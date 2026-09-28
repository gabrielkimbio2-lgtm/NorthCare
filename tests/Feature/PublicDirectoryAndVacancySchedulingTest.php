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
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicDirectoryAndVacancySchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_registered_public_user_can_search_and_filter_by_city_region_name_specialty_and_service(): void
    {
        $this->seed(DatabaseSeeder::class);

        $nicosia = City::query()->where('name', 'Nicosia')->firstOrFail();
        $nicosiaCentral = Region::query()->where('city_id', $nicosia->id)->where('name', 'Central')->firstOrFail();

        $famagusta = City::query()->where('name', 'Famagusta')->firstOrFail();
        $famagustaOldTown = Region::query()->where('city_id', $famagusta->id)->where('name', 'Old Town')->firstOrFail();

        $dentist = Category::query()->where('name', 'Dentist')->firstOrFail();
        $dietitian = Category::query()->where('name', 'Dietitian')->firstOrFail();

        $examService = Service::query()->where('name', 'Dental examination')->firstOrFail();
        $nutritionService = Service::query()->where('name', 'Nutrition consultation')->firstOrFail();

        // Public practitioner 1
        $doctor1 = PractitionerProfile::create([
            'category_id' => $dentist->id,
            'city_id' => $nicosia->id,
            'region_id' => $nicosiaCentral->id,
            'name' => 'Dr. Dennis Nicosia',
            'is_published' => true,
            'visibility' => ['name' => 'public', 'category' => 'public', 'city' => 'public', 'region' => 'public', 'address' => 'public'],
            'address' => 'Central Nicosia Blvd',
        ]);
        $doctor1->services()->attach($examService->id, ['duration_minutes' => 30]);

        // Public practitioner 2
        $doctor2 = PractitionerProfile::create([
            'category_id' => $dietitian->id,
            'city_id' => $famagusta->id,
            'region_id' => $famagustaOldTown->id,
            'name' => 'Dr. Diane Famagusta',
            'is_published' => true,
            'visibility' => ['name' => 'public', 'category' => 'public', 'city' => 'public', 'region' => 'public', 'address' => 'public'],
            'address' => 'Old Town Harbor',
        ]);
        $doctor2->services()->attach($nutritionService->id, ['duration_minutes' => 45]);

        // Private practitioner (wishes NOT to be seen by public)
        $privateDoctor = PractitionerProfile::create([
            'category_id' => $dentist->id,
            'city_id' => $nicosia->id,
            'region_id' => $nicosiaCentral->id,
            'name' => 'Dr. Secret Hidden',
            'is_published' => true,
            'visibility' => ['name' => 'registered'],
        ]);

        // 1. Unregistered visitor searches by City
        $response = $this->get('/?city='.$nicosia->id);
        $response->assertOk()
            ->assertSee('Dr. Dennis Nicosia')
            ->assertDontSee('Dr. Diane Famagusta')
            ->assertDontSee('Dr. Secret Hidden');

        // 2. Unregistered visitor filters by Region
        $response = $this->get('/?region='.$famagustaOldTown->id);
        $response->assertOk()
            ->assertSee('Dr. Diane Famagusta')
            ->assertDontSee('Dr. Dennis Nicosia');

        // 3. Unregistered visitor filters by Name search
        $response = $this->get('/?search=Dennis');
        $response->assertOk()
            ->assertSee('Dr. Dennis Nicosia')
            ->assertDontSee('Dr. Diane Famagusta');

        // 4. Unregistered visitor filters by Specialty
        $response = $this->get('/?specialty='.$dentist->id);
        $response->assertOk()
            ->assertSee('Dr. Dennis Nicosia')
            ->assertDontSee('Dr. Diane Famagusta');

        // 5. Unregistered visitor filters by Service
        $response = $this->get('/?service='.$nutritionService->id);
        $response->assertOk()
            ->assertSee('Dr. Diane Famagusta')
            ->assertDontSee('Dr. Dennis Nicosia');
    }

    public function test_public_users_can_filter_by_care_type_doctors_specialists_hospitals_and_institutions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $nicosia = City::query()->where('name', 'Nicosia')->firstOrFail();
        $dentist = Category::query()->where('name', 'Dentist')->firstOrFail(); // kind: doctor
        $psychologist = Category::query()->where('name', 'Psychologist')->firstOrFail(); // kind: specialist
        $hospitalCat = Category::query()->where('name', 'General hospital')->firstOrFail(); // kind: hospital

        // Doctor
        PractitionerProfile::create([
            'category_id' => $dentist->id,
            'city_id' => $nicosia->id,
            'name' => 'Dr. Arthur Dent',
            'is_published' => true,
            'visibility' => ['name' => 'public'],
        ]);

        // Specialist
        PractitionerProfile::create([
            'category_id' => $psychologist->id,
            'city_id' => $nicosia->id,
            'name' => 'Dr. Sigmund Specialist',
            'is_published' => true,
            'visibility' => ['name' => 'public'],
        ]);

        // Hospital Institution
        $hospitalInst = Institution::create([
            'name' => 'Nicosia General Hospital',
            'is_published' => true,
            'visibility' => ['name' => 'public'],
        ]);
        $hospitalInst->categories()->attach($hospitalCat->id);
        $hospitalInst->locations()->create([
            'city_id' => $nicosia->id,
            'name' => 'Main Wing',
            'address' => 'Hospital Blvd',
            'is_published' => true,
            'visibility' => ['address' => 'public'],
        ]);

        // Filter: Doctors only
        $response = $this->get('/?type=doctor');
        $response->assertOk()
            ->assertSee('Dr. Arthur Dent')
            ->assertDontSee('Dr. Sigmund Specialist')
            ->assertDontSee('Nicosia General Hospital');

        // Filter: Specialists only
        $response = $this->get('/?type=specialist');
        $response->assertOk()
            ->assertSee('Dr. Sigmund Specialist')
            ->assertDontSee('Dr. Arthur Dent')
            ->assertDontSee('Nicosia General Hospital');

        // Filter: Hospitals only
        $response = $this->get('/?type=hospital');
        $response->assertOk()
            ->assertSee('Nicosia General Hospital')
            ->assertDontSee('Dr. Arthur Dent')
            ->assertDontSee('Dr. Sigmund Specialist');

        // Filter: All practitioners (doctors & specialists)
        $response = $this->get('/?type=practitioner');
        $response->assertOk()
            ->assertSee('Dr. Arthur Dent')
            ->assertSee('Dr. Sigmund Specialist')
            ->assertDontSee('Nicosia General Hospital');

        // Filter: All
        $response = $this->get('/?type=all');
        $response->assertOk()
            ->assertSee('Dr. Arthur Dent')
            ->assertSee('Dr. Sigmund Specialist')
            ->assertSee('Nicosia General Hospital');
    }

    public function test_doctor_can_assign_duration_to_services_in_15_minute_intervals(): void
    {
        $this->seed(DatabaseSeeder::class);
        $city = City::query()->where('name', 'Nicosia')->firstOrFail();
        $category = Category::query()->where('name', 'Dentist')->firstOrFail();

        $user = User::factory()->create(['role' => UserRole::Practitioner, 'account_status' => 'active']);
        $profile = $user->practitionerProfile()->create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'name' => 'Dr. Slot Example',
            'is_published' => true,
        ]);

        $service1 = Service::query()->where('name', 'Dental examination')->firstOrFail();
        $service2 = Service::query()->where('name', 'Teeth cleaning')->firstOrFail();

        // Valid 15-minute interval durations (45 min and 60 min)
        $this->from(route('practitioner.profile.edit'))
            ->actingAs($user)
            ->put(route('practitioner.profile.services.update'), [
                'services' => [$service1->id, $service2->id],
                'service_durations' => [
                    $service1->id => 45,
                    $service2->id => 60,
                ],
            ])
            ->assertRedirect(route('practitioner.profile.edit'))
            ->assertSessionHas('status', 'Services updated.');

        $this->assertDatabaseHas('practitioner_profile_service', [
            'practitioner_profile_id' => $profile->id,
            'service_id' => $service1->id,
            'duration_minutes' => 45,
        ]);

        $this->assertDatabaseHas('practitioner_profile_service', [
            'practitioner_profile_id' => $profile->id,
            'service_id' => $service2->id,
            'duration_minutes' => 60,
        ]);

        // Invalid duration not aligned to 15-minute intervals (e.g. 25 minutes)
        $this->from(route('practitioner.profile.edit'))
            ->actingAs($user)
            ->put(route('practitioner.profile.services.update'), [
                'services' => [$service1->id],
                'service_durations' => [
                    $service1->id => 25,
                ],
            ])
            ->assertSessionHasErrors('service_durations.'.$service1->id);
    }

    public function test_doctor_can_set_weekly_working_days_and_hours_in_15_minute_intervals(): void
    {
        $this->seed(DatabaseSeeder::class);
        $city = City::query()->where('name', 'Nicosia')->firstOrFail();
        $category = Category::query()->where('name', 'Dentist')->firstOrFail();

        $user = User::factory()->create(['role' => UserRole::Practitioner, 'account_status' => 'active']);
        $profile = $user->practitionerProfile()->create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'name' => 'Dr. Weekly Schedule',
            'is_published' => true,
        ]);

        // Save Monday (09:00 - 17:00) and Friday (10:15 - 14:45), disable other days
        $this->from(route('practitioner.profile.edit'))
            ->actingAs($user)
            ->put(route('practitioner.profile.availability.update'), [
                'windows' => [
                    ['day_of_week' => 1, 'enabled' => true, 'starts_at' => '09:00', 'ends_at' => '17:00'],
                    ['day_of_week' => 2, 'enabled' => false],
                    ['day_of_week' => 3, 'enabled' => false],
                    ['day_of_week' => 4, 'enabled' => false],
                    ['day_of_week' => 5, 'enabled' => true, 'starts_at' => '10:15', 'ends_at' => '14:45'],
                    ['day_of_week' => 6, 'enabled' => false],
                    ['day_of_week' => 7, 'enabled' => false],
                ],
            ])
            ->assertRedirect(route('practitioner.profile.edit'))
            ->assertSessionHas('status', 'Weekly availability saved.');

        $this->assertDatabaseHas('availability_windows', [
            'practitioner_profile_id' => $profile->id,
            'day_of_week' => 1,
            'starts_at' => '09:00',
            'ends_at' => '17:00',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('availability_windows', [
            'practitioner_profile_id' => $profile->id,
            'day_of_week' => 5,
            'starts_at' => '10:15',
            'ends_at' => '14:45',
            'is_active' => true,
        ]);

        $this->assertDatabaseMissing('availability_windows', [
            'practitioner_profile_id' => $profile->id,
            'day_of_week' => 2,
        ]);
    }

    public function test_doctor_can_schedule_vacation_and_manual_schedule_alterations(): void
    {
        $this->seed(DatabaseSeeder::class);
        $city = City::query()->where('name', 'Nicosia')->firstOrFail();
        $category = Category::query()->where('name', 'Dentist')->firstOrFail();

        $user = User::factory()->create(['role' => UserRole::Practitioner, 'account_status' => 'active']);
        $profile = $user->practitionerProfile()->create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'name' => 'Dr. Vacation Example',
            'is_published' => true,
        ]);

        $today = today()->toDateString();
        $nextWeek = today()->addDays(2)->toDateString();

        // 1. Single day full vacation
        $this->from(route('practitioner.profile.edit'))
            ->actingAs($user)
            ->post(route('practitioner.profile.unavailable-times.store'), [
                'date' => $today,
                'reason' => 'Annual Leave',
            ])
            ->assertRedirect(route('practitioner.profile.edit'));

        $this->assertDatabaseHas('availability_overrides', [
            'practitioner_profile_id' => $profile->id,
            'starts_at' => null,
            'ends_at' => null,
            'reason' => 'Annual Leave',
        ]);
        $this->assertSame($today, Carbon::parse($profile->availabilityOverrides()->where('reason', 'Annual Leave')->value('date'))->toDateString());

        // 2. Partial timeslot override (e.g. 13:00 to 15:30)
        $this->from(route('practitioner.profile.edit'))
            ->actingAs($user)
            ->post(route('practitioner.profile.unavailable-times.store'), [
                'date' => $nextWeek,
                'starts_at' => '13:00',
                'ends_at' => '15:30',
                'reason' => 'Dentistry Seminar',
            ])
            ->assertRedirect(route('practitioner.profile.edit'));

        $this->assertDatabaseHas('availability_overrides', [
            'practitioner_profile_id' => $profile->id,
            'starts_at' => '13:00',
            'ends_at' => '15:30',
            'reason' => 'Dentistry Seminar',
        ]);
        $this->assertSame($nextWeek, Carbon::parse($profile->availabilityOverrides()->where('reason', 'Dentistry Seminar')->value('date'))->toDateString());

        // 3. Multi-day vacation range
        $startRange = today()->addDays(5)->toDateString();
        $endRange = today()->addDays(7)->toDateString();

        $this->from(route('practitioner.profile.edit'))
            ->actingAs($user)
            ->post(route('practitioner.profile.unavailable-times.store'), [
                'date' => $startRange,
                'end_date' => $endRange,
                'reason' => 'Summer Holiday',
            ])
            ->assertRedirect(route('practitioner.profile.edit'));

        $this->assertDatabaseHas('availability_overrides', [
            'practitioner_profile_id' => $profile->id,
            'reason' => 'Summer Holiday',
        ]);
        $this->assertCount(3, $profile->availabilityOverrides()->where('reason', 'Summer Holiday')->get());

        // 4. Remove an override
        $override = $profile->availabilityOverrides()->where('reason', 'Annual Leave')->firstOrFail();
        $this->from(route('practitioner.profile.edit'))
            ->actingAs($user)
            ->delete(route('practitioner.profile.unavailable-times.destroy', $override))
            ->assertRedirect(route('practitioner.profile.edit'));

        $this->assertDatabaseMissing('availability_overrides', ['id' => $override->id]);
    }

    public function test_system_generates_vacancy_booking_slots_varying_by_service_duration_and_respecting_vacations(): void
    {
        $this->seed(DatabaseSeeder::class);
        $city = City::query()->where('name', 'Nicosia')->firstOrFail();
        $category = Category::query()->where('name', 'Dentist')->firstOrFail();

        $user = User::factory()->create(['role' => UserRole::Practitioner, 'account_status' => 'active']);
        $profile = $user->practitionerProfile()->create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'name' => 'Dr. Slot Calculator',
            'is_published' => true,
        ]);

        // Set Monday (day 1) working 09:00 - 12:00
        $profile->availabilityWindows()->create([
            'day_of_week' => 1,
            'starts_at' => '09:00',
            'ends_at' => '12:00',
            'is_active' => true,
        ]);
        $profile->refresh();

        // Find next Monday
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);

        // Case A: 30-minute service duration -> 6 slots (09:00, 09:30, 10:00, 10:30, 11:00, 11:30)
        $slots30 = $profile->generateSlotsForDate($nextMonday, 30);
        $this->assertCount(6, $slots30);
        $this->assertSame('09:00', $slots30[0]['start']);
        $this->assertSame('09:30', $slots30[0]['end']);
        $this->assertSame('11:30', $slots30[5]['start']);
        $this->assertSame('12:00', $slots30[5]['end']);

        // Case B: 45-minute service duration -> 4 slots (09:00, 09:45, 10:30, 11:15)
        $slots45 = $profile->generateSlotsForDate($nextMonday, 45);
        $this->assertCount(4, $slots45);
        $this->assertSame('09:00', $slots45[0]['start']);
        $this->assertSame('09:45', $slots45[0]['end']);
        $this->assertSame('11:15', $slots45[3]['start']);
        $this->assertSame('12:00', $slots45[3]['end']);

        // Case C: Partial vacation (10:00 to 11:00) blocks overlapping slots
        $profile->availabilityOverrides()->create([
            'date' => $nextMonday->toDateString(),
            'starts_at' => '10:00',
            'ends_at' => '11:00',
            'reason' => 'Meeting',
        ]);
        $profile->refresh();

        $slotsAfterPartialVacation = $profile->generateSlotsForDate($nextMonday, 30);
        // 09:00-09:30, 09:30-10:00, 11:00-11:30, 11:30-12:00 (4 slots remaining)
        $this->assertCount(4, $slotsAfterPartialVacation);
        $this->assertSame(['09:00', '09:30', '11:00', '11:30'], array_column($slotsAfterPartialVacation, 'start'));

        // Case D: Full-day vacation on that date blocks ALL slots
        $profile->availabilityOverrides()->create([
            'date' => $nextMonday->toDateString(),
            'starts_at' => null,
            'ends_at' => null,
            'reason' => 'Full day vacation',
        ]);
        $profile->refresh();

        $slotsFullVacation = $profile->generateSlotsForDate($nextMonday, 30);
        $this->assertEmpty($slotsFullVacation);
        $this->assertTrue($profile->hasVacationOnDate($nextMonday));
    }

    public function test_directory_displays_public_service_durations_weekly_schedule_and_leave_notices(): void
    {
        $this->seed(DatabaseSeeder::class);
        $city = City::query()->where('name', 'Nicosia')->firstOrFail();
        $category = Category::query()->where('name', 'Dentist')->firstOrFail();
        $service = Service::query()->where('name', 'Dental examination')->firstOrFail();

        $profile = PractitionerProfile::create([
            'category_id' => $category->id,
            'city_id' => $city->id,
            'name' => 'Dr. Visible Calendar',
            'is_published' => true,
            'visibility' => ['name' => 'public'],
        ]);

        $profile->services()->attach($service->id, ['duration_minutes' => 45]);
        $profile->availabilityWindows()->create([
            'day_of_week' => 1,
            'starts_at' => '09:00',
            'ends_at' => '17:00',
            'is_active' => true,
        ]);
        $profile->availabilityOverrides()->create([
            'date' => today()->addDays(3)->toDateString(),
            'reason' => 'Dental Convention',
        ]);

        $response = $this->get('/');
        $response->assertOk()
            ->assertSee('Dr. Visible Calendar')
            ->assertSee('Dental examination (45 min)')
            ->assertSee('Mon: 09:00 - 17:00')
            ->assertSee('Dental Convention');
    }
}
