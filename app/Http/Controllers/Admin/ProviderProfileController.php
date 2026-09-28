<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Institution;
use App\Models\PractitionerProfile;
use App\Models\Region;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProviderProfileController extends Controller
{
    public function create(): View
    {
        return view('admin.profiles.create', [
            'practitionerCategories' => Category::query()->whereNotNull('parent_id')->whereIn('kind', ['doctor', 'specialist'])->where('is_active', true)->with('parent')->orderBy('name')->get(),
            'hospitalCategories' => Category::query()->whereNotNull('parent_id')->where('kind', 'hospital')->where('is_active', true)->with('parent')->orderBy('name')->get(),
            'cities' => City::query()->where('is_active', true)->with(['regions' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'services' => Service::query()->where('is_active', true)->orderBy('name')->get(),
            'institutions' => Institution::query()->orderBy('name')->get(),
        ]);
    }

    public function storePractitioner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:4000'],
            'visibility' => ['nullable', 'array:name,contact_email,phone,address,bio'],
            'visibility.*' => [Rule::in(['public', 'registered', 'private'])],
            'service_ids' => ['nullable', 'array', 'max:30'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
        ]);

        $category = Category::query()->with('parent')->findOrFail($validated['category_id']);
        $this->assertCategoryKind($category, ['doctor', 'specialist']);
        $this->assertRegionMatchesCity($validated['region_id'] ?? null, (int) $validated['city_id']);

        $profile = DB::transaction(function () use ($validated): PractitionerProfile {
            $profile = PractitionerProfile::create([
                'category_id' => $validated['category_id'],
                'city_id' => $validated['city_id'],
                'region_id' => $validated['region_id'] ?? null,
                'name' => $validated['name'],
                'contact_email' => $validated['contact_email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'visibility' => array_merge($this->defaultVisibility(), $validated['visibility'] ?? []),
                'is_published' => true,
            ]);
            $profile->services()->sync($validated['service_ids'] ?? []);

            return $profile;
        });

        return redirect()->route('admin.profiles.create')->with('status', 'Practitioner profile #'.$profile->id.' created and published.');
    }

    public function storeInstitution(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:4000'],
            'category_ids' => ['required', 'array', 'min:1', 'max:20'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'location_name' => ['nullable', 'string', 'max:160'],
            'address' => ['required', 'string', 'max:255'],
            'visibility' => ['nullable', 'array:name,contact_email,phone,description'],
            'visibility.*' => [Rule::in(['public', 'registered', 'private'])],
        ]);

        foreach ($validated['category_ids'] as $categoryId) {
            $this->assertCategoryKind(Category::query()->with('parent')->findOrFail($categoryId), ['hospital']);
        }
        $this->assertRegionMatchesCity($validated['region_id'] ?? null, (int) $validated['city_id']);

        $institution = DB::transaction(function () use ($validated): Institution {
            $institution = Institution::create([
                'name' => $validated['name'],
                'contact_email' => $validated['contact_email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'description' => $validated['description'] ?? null,
                'visibility' => array_merge($this->defaultVisibility(), $validated['visibility'] ?? []),
                'is_published' => true,
            ]);
            $institution->categories()->sync($validated['category_ids']);
            $institution->locations()->create([
                'city_id' => $validated['city_id'],
                'region_id' => $validated['region_id'] ?? null,
                'name' => $validated['location_name'] ?? null,
                'address' => $validated['address'],
                'contact_email' => $validated['contact_email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'visibility' => array_merge($this->defaultVisibility(), $validated['visibility'] ?? []),
                'is_published' => true,
            ]);

            return $institution;
        });

        return redirect()->route('admin.profiles.create')->with('status', 'Institution profile #'.$institution->id.' created with its first location.');
    }

    public function storeInstitutionLocation(Request $request, Institution $institution): RedirectResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'name' => ['nullable', 'string', 'max:160'],
            'address' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        $this->assertRegionMatchesCity($validated['region_id'] ?? null, (int) $validated['city_id']);

        $location = $institution->locations()->create([
            ...$validated,
            'visibility' => $institution->visibility,
            'is_published' => true,
        ]);

        return redirect()->route('admin.profiles.create')->with('status', 'Location #'.$location->id.' added to '.$institution->name.'.');
    }

    private function assertCategoryKind(Category $category, array $allowedKinds): void
    {
        if (
            ! $category->is_active
            || $category->parent === null
            || $category->parent->parent_id !== null
            || ! in_array($category->kind, $allowedKinds, true)
            || $category->parent->kind !== $category->kind
        ) {
            throw ValidationException::withMessages(['category_id' => 'Select an active second-level category for this profile.']);
        }
    }

    private function assertRegionMatchesCity(?int $regionId, int $cityId): void
    {
        if ($regionId !== null && ! Region::query()->whereKey($regionId)->where('city_id', $cityId)->exists()) {
            throw ValidationException::withMessages(['region_id' => 'Select a region that belongs to the chosen city.']);
        }
    }

    private function defaultVisibility(): array
    {
        return [
            'name' => 'public',
            'contact_email' => 'registered',
            'phone' => 'registered',
            'address' => 'registered',
            'description' => 'public',
            'bio' => 'public',
        ];
    }
}
