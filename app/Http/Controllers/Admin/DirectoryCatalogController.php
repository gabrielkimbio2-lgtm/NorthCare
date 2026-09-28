<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Language;
use App\Models\Region;
use App\Models\Service;
use App\Models\ServiceSuggestion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DirectoryCatalogController extends Controller
{
    public function index(): View
    {
        return view('admin.catalog.index', [
            'cities' => City::query()->with('regions')->orderBy('name')->get(),
            'rootCategories' => Category::query()->whereNull('parent_id')->with('children')->orderBy('kind')->get(),
            'services' => Service::query()->orderBy('name')->get(),
            'suggestions' => ServiceSuggestion::query()->with('submitter')->where('status', 'pending')->latest()->get(),
            'languages' => Language::query()->where('is_active', true)->where('is_default', false)->orderBy('name')->get(),
        ]);
    }

    public function storeCity(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $slug = Str::slug($validated['name']);

        if ($slug === '' || City::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'A city with this name already exists or has an invalid slug.']);
        }

        $city = City::create(['name' => $validated['name'], 'slug' => $slug]);
        $this->saveNameTranslations($city, $request->input('translations', []));

        return back()->with('status', 'City added.');
    }

    public function storeRegion(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'name' => ['required', 'string', 'max:120'],
        ]);
        $slug = Str::slug($validated['name']);

        if ($slug === '' || Region::query()->where('city_id', $validated['city_id'])->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'A region with this name already exists in that city.']);
        }

        $region = Region::create(['city_id' => $validated['city_id'], 'name' => $validated['name'], 'slug' => $slug]);
        $this->saveNameTranslations($region, $request->input('translations', []));

        return back()->with('status', 'Region added.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'kind' => [Rule::requiredIf(! $request->filled('parent_id')), Rule::in(['doctor', 'specialist', 'hospital'])],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);
        $parent = isset($validated['parent_id']) ? Category::query()->findOrFail($validated['parent_id']) : null;

        if ($parent !== null && $parent->parent_id !== null) {
            throw ValidationException::withMessages(['parent_id' => 'Categories can have only two levels.']);
        }

        $kind = $parent?->kind ?? $validated['kind'];
        $slug = Str::slug($validated['name']);

        if ($slug === '' || Category::query()->where('parent_id', $parent?->id)->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'A category with this name already exists at that level.']);
        }

        $category = Category::create([
            'parent_id' => $parent?->id,
            'kind' => $kind,
            'name' => $validated['name'],
            'slug' => $slug,
        ]);
        $this->saveNameTranslations($category, $request->input('translations', []));

        return back()->with('status', 'Category added.');
    }

    public function storeService(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $slug = Str::slug($validated['name']);

        if ($slug === '' || Service::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'A service with this name already exists or has an invalid slug.']);
        }

        $service = Service::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);
        $this->saveNameTranslations($service, $request->input('translations', []));

        return back()->with('status', 'Service added.');
    }

    public function updateService(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);
        $slug = Str::slug($validated['name']);

        if ($slug === '' || Service::query()->where('slug', $slug)->whereKeyNot($service->id)->exists()) {
            throw ValidationException::withMessages(['name' => 'A service with this name already exists or has an invalid slug.']);
        }

        $service->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        $this->saveNameTranslations($service, $request->input('translations', []));

        return back()->with('status', 'Service updated.');
    }

    public function reviewSuggestion(Request $request, ServiceSuggestion $serviceSuggestion): RedirectResponse
    {
        abort_unless($serviceSuggestion->status === 'pending', 404);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $serviceSuggestion, $validated): void {
            if ($validated['decision'] === 'approve') {
                $service = isset($validated['service_id'])
                    ? Service::query()->lockForUpdate()->findOrFail($validated['service_id'])
                    : new Service;
                $slug = Str::slug($validated['name']);

                if ($slug === '' || Service::query()->where('slug', $slug)->whereKeyNot($service->id)->exists()) {
                    throw ValidationException::withMessages(['name' => 'A service with this name already exists or has an invalid slug.']);
                }

                $service->fill([
                    'name' => $validated['name'],
                    'slug' => $slug,
                    'description' => $validated['description'] ?? null,
                    'is_active' => true,
                ]);
                $service->save();
                $serviceSuggestion->service_id = $service->id;
                $serviceSuggestion->status = 'approved';
            } else {
                $serviceSuggestion->status = 'rejected';
            }

            $serviceSuggestion->reviewed_by = $request->user()->id;
            $serviceSuggestion->reviewed_at = Carbon::now();
            $serviceSuggestion->review_notes = $validated['review_notes'] ?? null;
            $serviceSuggestion->save();
        });

        return back()->with('status', 'Service suggestion '.$validated['decision'].'d.');
    }

    private function saveNameTranslations(Model $model, mixed $translations): void
    {
        if (! is_array($translations)) {
            throw ValidationException::withMessages(['translations' => 'Translations must be submitted as a language list.']);
        }

        foreach ($translations as $languageId => $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $language = Language::query()->where('is_active', true)->find($languageId);

            if ($language === null) {
                throw ValidationException::withMessages(['translations' => 'Select an active language for every translation.']);
            }

            $model->contentTranslations()->updateOrCreate(
                ['language_id' => $language->id, 'field' => 'name'],
                ['value' => trim($value)],
            );
        }
    }
}
