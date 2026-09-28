<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ReviewProfileApplication;
use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\Category;
use App\Models\City;
use App\Models\Institution;
use App\Models\InstitutionLocation;
use App\Models\PractitionerProfile;
use App\Models\ProfileApplication;
use App\Models\Region;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileApplicationController extends Controller
{
    public function index(): View
    {
        return view('admin.applications.index', [
            'applications' => ProfileApplication::query()
                ->with(['applicant', 'category.parent', 'city'])
                ->where('status', 'pending')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function edit(ProfileApplication $profileApplication): View
    {
        abort_unless($profileApplication->status === 'pending', 404);

        $profileApplication->load(['applicant', 'category.parent', 'city', 'region', 'documents']);

        return view('admin.applications.edit', [
            'application' => $profileApplication,
            'categories' => Category::query()->whereNotNull('parent_id')->where('is_active', true)->with('parent')->orderBy('name')->get(),
            'cities' => City::query()->where('is_active', true)->with(['regions' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'practitionerProfiles' => PractitionerProfile::query()
                ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $profileApplication->applicant_user_id))
                ->with(['category', 'city'])
                ->orderBy('name')
                ->get(),
            'institutions' => Institution::query()
                ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $profileApplication->applicant_user_id))
                ->orderBy('name')
                ->get(),
            'institutionLocations' => InstitutionLocation::query()
                ->whereHas('institution', fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $profileApplication->applicant_user_id))
                ->with('institution')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, ProfileApplication $profileApplication, ReviewProfileApplication $review): RedirectResponse
    {
        abort_unless($profileApplication->status === 'pending', 404);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'name' => ['required', 'string', 'max:160'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'visibility' => ['nullable', 'array:name,contact_email,phone,address,description'],
            'visibility.name' => ['nullable', Rule::in(['public', 'registered', 'private'])],
            'visibility.contact_email' => ['nullable', Rule::in(['public', 'registered', 'private'])],
            'visibility.phone' => ['nullable', Rule::in(['public', 'registered', 'private'])],
            'visibility.address' => ['nullable', Rule::in(['public', 'registered', 'private'])],
            'visibility.description' => ['nullable', Rule::in(['public', 'registered', 'private'])],
            'matched_practitioner_profile_id' => ['nullable', 'integer', 'exists:practitioner_profiles,id'],
            'matched_institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'matched_institution_location_id' => ['nullable', 'integer', 'exists:institution_locations,id'],
            'admin_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $region = isset($validated['region_id']) ? Region::query()->find($validated['region_id']) : null;

        if ($region !== null && $region->city_id !== (int) $validated['city_id']) {
            throw ValidationException::withMessages(['region_id' => 'Select a region that belongs to the chosen city.']);
        }

        $category = Category::query()->with('parent')->findOrFail($validated['category_id']);
        $allowedKinds = $profileApplication->application_type === UserRole::Institution->value
            ? ['hospital']
            : ['doctor', 'specialist'];

        if (
            ! $category->is_active
            || $category->parent === null
            || $category->parent->parent_id !== null
            || ! in_array($category->kind, $allowedKinds, true)
            || $category->parent->kind !== $category->kind
        ) {
            throw ValidationException::withMessages(['category_id' => 'Select an active second-level category for this application.']);
        }

        if ($profileApplication->application_type === UserRole::Practitioner->value) {
            $validated['matched_institution_id'] = null;
            $validated['matched_institution_location_id'] = null;
        } else {
            $validated['matched_practitioner_profile_id'] = null;
        }

        $review->handle($profileApplication, User::query()->findOrFail($request->user()->getAuthIdentifier()), $validated);

        $status = $validated['decision'] === 'approve' ? 'approved' : 'rejected';

        return redirect()->route('admin.applications.index')->with('status', 'Application '.$status.'.');
    }

    public function downloadDocument(ProfileApplication $profileApplication, ApplicationDocument $document): StreamedResponse
    {
        abort_unless($document->profile_application_id === $profileApplication->id, 404);

        $stream = Storage::disk('local')->readStream($document->storage_path);
        abort_unless(is_resource($stream), 404);

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $document->original_name, ['Content-Type' => $document->mime_type]);
    }
}
