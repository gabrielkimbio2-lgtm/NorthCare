<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function index(): View
    {
        $defaultLanguage = Language::query()->where('is_default', true)->first();

        return view('admin.languages.index', [
            'languages' => Language::query()->with('uiTranslations')->orderBy('name')->get(),
            'translationKeys' => $defaultLanguage?->uiTranslations()->orderBy('key')->get() ?? collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:16', 'regex:/^[a-z]{2,3}(?:-[A-Z]{2})?$/', Rule::unique('languages', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['required', 'string', 'max:100'],
            'translations' => ['required', 'array'],
        ]);
        $translationValues = $this->translationValues($validated['translations']);

        DB::transaction(function () use ($validated, $translationValues): void {
            $language = Language::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'native_name' => $validated['native_name'],
                'is_default' => false,
                'is_active' => true,
            ]);

            foreach ($translationValues as $key => $value) {
                $language->uiTranslations()->create(['key' => $key, 'value' => $value]);
            }
        });

        return back()->with('status', 'Language added with its UI translations.');
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'translations' => ['required', 'array'],
        ]);
        $translationValues = $this->translationValues($validated['translations']);

        if ($language->is_default && ! $request->boolean('is_active')) {
            throw ValidationException::withMessages(['is_active' => 'The default language must remain active.']);
        }

        DB::transaction(function () use ($language, $validated, $translationValues): void {
            $language->update([
                'name' => $validated['name'],
                'native_name' => $validated['native_name'],
                'is_active' => $validated['is_active'],
            ]);

            foreach ($translationValues as $key => $value) {
                $language->uiTranslations()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        return back()->with('status', 'Language translations updated.');
    }

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::exists('languages', 'code')->where('is_active', true)],
        ]);

        $request->session()->put('locale', $validated['locale']);

        if ($request->user() !== null) {
            $request->user()->preferred_locale = $validated['locale'];
            $request->user()->save();
        }

        return back();
    }

    private function translationValues(array $submittedTranslations): array
    {
        $defaultLanguage = Language::query()->where('is_default', true)->first();

        if ($defaultLanguage === null) {
            throw ValidationException::withMessages(['translations' => 'Seed the default language before adding translations.']);
        }

        $translationValues = [];

        foreach ($defaultLanguage->uiTranslations()->orderBy('key')->get() as $defaultTranslation) {
            $value = $submittedTranslations[$defaultTranslation->key] ?? null;

            if (! is_string($value) || trim($value) === '' || mb_strlen($value) > 500) {
                throw ValidationException::withMessages(['translations' => 'Provide a translation up to 500 characters for every interface label.']);
            }

            $translationValues[$defaultTranslation->key] = trim($value);
        }

        return $translationValues;
    }
}
