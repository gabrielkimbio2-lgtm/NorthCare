<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\InstitutionLocation;
use App\Models\PractitionerProfile;
use App\Models\ProfileApplication;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewProfileApplication
{
    public function handle(ProfileApplication $application, User $reviewer, array $data): ProfileApplication
    {
        return DB::transaction(function () use ($application, $reviewer, $data): ProfileApplication {
            $application = ProfileApplication::query()->lockForUpdate()->findOrFail($application->id);

            if ($application->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'This application has already been reviewed.']);
            }

            $application->fill(Arr::only($data, [
                'category_id',
                'city_id',
                'region_id',
                'name',
                'contact_email',
                'phone',
                'address',
                'description',
                'visibility',
                'admin_notes',
                'matched_practitioner_profile_id',
                'matched_institution_id',
                'matched_institution_location_id',
            ]));
            $application->reviewed_by = $reviewer->id;
            $application->reviewed_at = now();

            $applicant = User::query()->findOrFail($application->applicant_user_id);

            if ($data['decision'] === 'reject') {
                $application->status = 'rejected';
                $application->save();
                $applicant->account_status = 'rejected';
                $applicant->save();

                return $application;
            }

            if ($application->application_type === UserRole::Practitioner->value) {
                $this->approvePractitioner($application, $applicant);
            } elseif ($application->application_type === UserRole::Institution->value) {
                $this->approveInstitution($application, $applicant);
            } else {
                throw ValidationException::withMessages(['decision' => 'This application type cannot be approved.']);
            }

            $application->status = 'approved';
            $application->save();
            $applicant->account_status = 'active';
            $applicant->save();

            return $application;
        });
    }

    private function approvePractitioner(ProfileApplication $application, User $applicant): void
    {
        $profile = $application->matchedPractitionerProfile()->lockForUpdate()->first();
        $profile ??= new PractitionerProfile;

        if ($profile->user_id !== null && $profile->user_id !== $applicant->id) {
            throw ValidationException::withMessages(['matched_practitioner_profile_id' => 'That profile is already connected to another account.']);
        }

        $profile->fill([
            'category_id' => $application->category_id,
            'city_id' => $application->city_id,
            'region_id' => $application->region_id,
            'name' => $application->name,
            'contact_email' => $application->contact_email,
            'phone' => $application->phone,
            'address' => $application->address,
            'bio' => $application->description,
            'visibility' => $application->visibility,
            'is_published' => true,
        ]);
        $profile->user_id = $applicant->id;
        $profile->save();

        $application->matched_practitioner_profile_id = $profile->id;
    }

    private function approveInstitution(ProfileApplication $application, User $applicant): void
    {
        $location = $application->matchedInstitutionLocation()->lockForUpdate()->first();
        $institution = $application->matchedInstitution()->lockForUpdate()->first()
            ?? $location?->institution
            ?? $applicant->institution()->lockForUpdate()->first();
        $institution ??= new Institution;

        if ($institution->user_id !== null && $institution->user_id !== $applicant->id) {
            throw ValidationException::withMessages(['matched_institution_id' => 'That institution is already connected to another account.']);
        }

        $institution->fill([
            'name' => $application->name,
            'contact_email' => $application->contact_email,
            'phone' => $application->phone,
            'description' => $application->description,
            'visibility' => $application->visibility,
            'is_published' => true,
        ]);
        $institution->user_id = $applicant->id;
        $institution->save();
        $institution->categories()->syncWithoutDetaching([$application->category_id]);

        if ($location !== null && $location->institution_id !== $institution->id) {
            throw ValidationException::withMessages(['matched_institution_location_id' => 'Choose a location belonging to the matched institution.']);
        }

        $location ??= new InstitutionLocation;
        $location->fill([
            'institution_id' => $institution->id,
            'city_id' => $application->city_id,
            'region_id' => $application->region_id,
            'name' => $application->address ?: $application->city?->name,
            'address' => $application->address,
            'contact_email' => $application->contact_email,
            'phone' => $application->phone,
            'visibility' => $application->visibility,
            'is_published' => true,
        ]);
        $location->save();

        $application->matched_institution_id = $institution->id;
        $application->matched_institution_location_id = $location->id;
    }
}
