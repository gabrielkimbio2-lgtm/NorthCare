<?php

namespace App\Http\Controllers\Institution;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\InstitutionLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $institution = $request->user()->institution()->with(['locations.city', 'locations.region', 'categories'])->firstOrFail();

        return view('institution.profile.edit', [
            'institution' => $institution,
            'cities' => City::query()->where('is_active', true)->with(['regions' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'categories' => Category::query()->where('is_active', true)->whereNull('parent_id')->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:4000'],
            'visibility' => ['required', 'array:name,contact_email,phone,description'],
            'visibility.name' => ['required', Rule::in(['public', 'registered', 'private'])],
            'visibility.contact_email' => ['required', Rule::in(['public', 'registered', 'private'])],
            'visibility.phone' => ['required', Rule::in(['public', 'registered', 'private'])],
            'visibility.description' => ['required', Rule::in(['public', 'registered', 'private'])],
        ]);

        $request->user()->institution()->firstOrFail()->update($validated);

        return back()->with('status', 'Institution profile and visibility saved.');
    }

    public function updateLocation(Request $request, InstitutionLocation $institutionLocation): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:160'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'address' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'visibility' => ['required', 'array:name,contact_email,phone,address'],
            'visibility.name' => ['required', Rule::in(['public', 'registered', 'private'])],
            'visibility.contact_email' => ['required', Rule::in(['public', 'registered', 'private'])],
            'visibility.phone' => ['required', Rule::in(['public', 'registered', 'private'])],
            'visibility.address' => ['required', Rule::in(['public', 'registered', 'private'])],
        ]);
        $institution = $request->user()->institution()->firstOrFail();
        $location = $institution->locations()->whereKey($institutionLocation->id)->firstOrFail();

        if (isset($validated['region_id']) && ! $location->region()->whereKey($validated['region_id'])->where('city_id', $validated['city_id'])->exists()) {
            throw ValidationException::withMessages(['region_id' => 'Select a region that belongs to the chosen city.']);
        }

        $location->update($validated);

        return back()->with('status', 'Institution location and visibility saved.');
    }
}
