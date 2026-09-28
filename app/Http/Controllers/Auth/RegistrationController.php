<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Category;
use App\Models\City;
use App\Models\Language;
use App\Models\ProfileApplication;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RegistrationController extends Controller
{
    public function create(): View
    {
        $locale = app()->getLocale();
        $localizedName = static function (?Model $model) use ($locale): string {
            if ($model === null) {
                return '';
            }

            return $model->contentTranslations
                ->first(fn ($translation): bool => $translation->field === 'name' && $translation->language?->code === $locale)
                ?->value ?? $model->name;
        };
        $categories = Category::query()
            ->whereNotNull('parent_id')
            ->where('is_active', true)
            ->with(['parent.contentTranslations.language', 'contentTranslations.language'])
            ->orderBy('name')
            ->get()
            ->each(function (Category $category) use ($localizedName): void {
                $category->setAttribute('localized_name', $localizedName($category));
                $category->setAttribute('parent_localized_name', $localizedName($category->parent));
            });
        $cities = City::query()
            ->where('is_active', true)
            ->with(['contentTranslations.language', 'regions' => fn ($query) => $query->where('is_active', true)->with('contentTranslations.language')->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->each(function (City $city) use ($localizedName): void {
                $city->setAttribute('localized_name', $localizedName($city));
                $city->regions->each(function ($region) use ($localizedName): void {
                    $region->setAttribute('localized_name', $localizedName($region));
                });
            });

        return view('auth.register', [
            'categories' => $categories,
            'cities' => $cities,
            'languages' => Language::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $role = UserRole::from($validated['account_type']);
        $isPatient = $role === UserRole::Patient;
        $storedPaths = [];

        try {
            $user = DB::transaction(function () use ($validated, $role, $isPatient, &$storedPaths): User {
                $user = new User([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'preferred_locale' => $validated['preferred_locale'] ?? app()->getLocale(),
                ]);
                $user->role = $role;
                $user->account_status = $isPatient ? 'active' : 'pending';
                $user->save();

                if ($isPatient) {
                    return $user;
                }

                $visibility = array_merge([
                    'name' => 'public',
                    'contact_email' => 'registered',
                    'phone' => 'registered',
                    'address' => 'registered',
                    'description' => 'public',
                ], $validated['visibility'] ?? []);

                $application = ProfileApplication::create([
                    'applicant_user_id' => $user->id,
                    'application_type' => $role->value,
                    'status' => 'pending',
                    'category_id' => $validated['category_id'],
                    'city_id' => $validated['city_id'],
                    'region_id' => $validated['region_id'] ?? null,
                    'name' => $validated['name'],
                    'contact_email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'address' => $validated['address'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'visibility' => $visibility,
                ]);

                foreach ($validated['documents'] as $document) {
                    $path = $document->store('applications/'.$application->id, 'local');

                    if ($path === false) {
                        throw new RuntimeException('The application document could not be stored.');
                    }

                    $storedPaths[] = $path;
                    $application->documents()->create([
                        'storage_path' => $path,
                        'original_name' => $document->getClientOriginalName(),
                        'mime_type' => $document->getMimeType() ?? 'application/octet-stream',
                        'byte_size' => $document->getSize() ?? 0,
                    ]);
                }

                return $user;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }

        Auth::login($user);
        $request->session()->regenerate();

        if (! $isPatient) {
            return redirect()->route('account.pending-approval');
        }

        return redirect()->route('dashboard')->with('status', 'Your account is ready.');
    }
}
