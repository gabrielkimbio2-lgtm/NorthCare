<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Institution;
use App\Models\PractitionerProfile;
use App\Models\Region;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'integer', 'exists:cities,id'],
            'region' => ['nullable', 'integer', 'exists:regions,id'],
            'specialty' => ['nullable', 'integer', 'exists:categories,id'],
            'service' => ['nullable', 'integer', 'exists:services,id'],
            'type' => ['nullable', Rule::in(['all', 'practitioner', 'institution', 'doctor', 'specialist', 'hospital'])],
        ]);
        $search = mb_strtolower(trim($filters['search'] ?? ''));
        $cityId = isset($filters['city']) ? (int) $filters['city'] : null;
        $regionId = isset($filters['region']) ? (int) $filters['region'] : null;
        $specialtyId = isset($filters['specialty']) ? (int) $filters['specialty'] : null;
        $serviceId = isset($filters['service']) ? (int) $filters['service'] : null;
        $type = $filters['type'] ?? 'all';
        $isRegistered = $request->user() !== null;
        $locale = app()->getLocale();
        $localizedName = static function ($model) use ($locale): string {
            if ($model === null) {
                return '';
            }

            return $model->contentTranslations
                ->first(fn ($translation): bool => $translation->field === 'name' && $translation->language?->code === $locale)
                ?->value ?? $model->name;
        };

        $includePractitioners = in_array($type, ['all', 'practitioner', 'doctor', 'specialist'], true);
        $practitioners = collect();

        if ($includePractitioners) {
            $practitioners = PractitionerProfile::query()
                ->where('is_published', true)
                ->with([
                    'category.parent.contentTranslations.language',
                    'category.contentTranslations.language',
                    'city.contentTranslations.language',
                    'region.contentTranslations.language',
                    'services' => fn ($query) => $query->where('is_active', true)->with('contentTranslations.language'),
                    'availabilityWindows' => fn ($query) => $query->where('is_active', true)->orderBy('day_of_week'),
                    'availabilityOverrides' => fn ($query) => $query->whereDate('date', '>=', today())->orderBy('date')->orderBy('starts_at'),
                ])
                ->when($type === 'doctor', fn ($query) => $query->whereHas('category', fn ($cat) => $cat->where('kind', 'doctor')->orWhereHas('parent', fn ($p) => $p->where('kind', 'doctor'))))
                ->when($type === 'specialist', fn ($query) => $query->whereHas('category', fn ($cat) => $cat->where('kind', 'specialist')->orWhereHas('parent', fn ($p) => $p->where('kind', 'specialist'))))
                ->when($cityId !== null, fn ($query) => $query->where('city_id', $cityId))
                ->when($regionId !== null, fn ($query) => $query->where('region_id', $regionId))
                ->when($specialtyId !== null, function ($query) use ($specialtyId) {
                    $categoryIds = Category::query()
                        ->whereKey($specialtyId)
                        ->orWhere('parent_id', $specialtyId)
                        ->pluck('id');
                    $query->whereIn('category_id', $categoryIds);
                })
                ->when($serviceId !== null, fn ($query) => $query->whereHas('services', fn ($serviceQuery) => $serviceQuery->whereKey($serviceId)->where('is_active', true)))
                ->orderBy('name')
                ->get()
                ->filter(function (PractitionerProfile $profile) use ($isRegistered, $localizedName, $search): bool {
                    if (! $profile->canViewField('name', $isRegistered)
                        || ! $profile->canViewField('category', $isRegistered)
                        || ! $profile->canViewField('city', $isRegistered)
                        || ! $profile->canViewField('region', $isRegistered)) {
                        return false;
                    }

                    if ($search === '') {
                        return true;
                    }

                    $searchableText = mb_strtolower(implode(' ', [
                        $profile->name,
                        $localizedName($profile->category),
                        $localizedName($profile->city),
                        $localizedName($profile->region),
                        $profile->services->map($localizedName)->implode(' '),
                    ]));

                    return str_contains($searchableText, $search);
                })
                ->map(function (PractitionerProfile $profile) use ($isRegistered, $localizedName): array {
                    $nameParts = preg_split('/\s+/u', trim($profile->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    $kind = $profile->category?->kind ?? $profile->category?->parent?->kind;
                    $typeLabel = match ($kind) {
                        'doctor' => 'Doctor',
                        'specialist' => 'Specialist',
                        default => 'Practitioner',
                    };

                    return [
                        'id' => $profile->id,
                        'name' => $profile->name,
                        'initials' => collect($nameParts)->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->implode(''),
                        'specialty' => $localizedName($profile->category),
                        'city' => $localizedName($profile->city),
                        'region' => $localizedName($profile->region),
                        'address' => $profile->canViewField('address', $isRegistered) ? $profile->address : null,
                        'services' => $profile->services->map(function ($service) use ($localizedName): string {
                            $name = $localizedName($service);
                            $duration = $service->pivot->duration_minutes;

                            return $duration ? sprintf('%s (%d min)', $name, $duration) : $name;
                        })->implode(', '),
                        'services_list' => $profile->services->map(fn ($s): array => [
                            'name' => $localizedName($s),
                            'duration' => $s->pivot->duration_minutes ?? 30,
                        ])->values()->all(),
                        'weekly_schedule' => $profile->getWeeklyScheduleSummary(),
                        'vacations' => $profile->availabilityOverrides->map(function ($override): string {
                            $date = Carbon::parse($override->date)->format('M d, Y');
                            if (filled($override->starts_at) && filled($override->ends_at)) {
                                $time = mb_substr($override->starts_at, 0, 5).' - '.mb_substr($override->ends_at, 0, 5);

                                return $date.' ('.$time.')'.($override->reason ? ': '.$override->reason : '');
                            }

                            return $date.' (Full day)'.($override->reason ? ': '.$override->reason : '');
                        })->values()->all(),
                        'approved_comments' => $profile->approvedComments->map(fn ($c): array => [
                            'author' => $c->user?->name ?? 'Patient',
                            'comment' => $c->comment,
                            'doctor_rating' => $c->doctor_rating,
                            'service_rating' => $c->service_rating,
                        ])->values()->all(),
                        'average_doctor_rating' => $profile->getAverageDoctorRating(),
                        'total_reviews' => $profile->getTotalReviewsCount(),
                        'branch' => null,
                        'type_label' => $typeLabel,
                    ];
                })
                ->values();
        }

        $canViewField = static function (?array $visibility, string $field) use ($isRegistered): bool {
            $fieldVisibility = $visibility[$field] ?? 'public';

            return $fieldVisibility === 'public' || ($isRegistered && $fieldVisibility === 'registered');
        };

        $includeInstitutions = in_array($type, ['all', 'institution', 'hospital'], true) && $serviceId === null;
        $institutions = collect();

        if ($includeInstitutions) {
            $institutions = Institution::query()
                ->where('is_published', true)
                ->when($type === 'hospital', fn ($query) => $query->whereHas('categories', fn ($cat) => $cat->where('kind', 'hospital')->orWhereHas('parent', fn ($p) => $p->where('kind', 'hospital'))))
                ->when(
                    $specialtyId !== null,
                    function ($query) use ($specialtyId) {
                        $categoryIds = Category::query()
                            ->whereKey($specialtyId)
                            ->orWhere('parent_id', $specialtyId)
                            ->pluck('id');
                        $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('categories.id', $categoryIds));
                    },
                )
                ->with([
                    'contentTranslations.language',
                    'categories.parent.contentTranslations.language',
                    'categories.contentTranslations.language',
                    'locations' => fn ($query) => $query
                        ->where('is_published', true)
                        ->with([
                            'city.contentTranslations.language',
                            'region.contentTranslations.language',
                            'contentTranslations.language',
                        ]),
                ])
                ->orderBy('name')
                ->get()
                ->flatMap(function (Institution $institution) use (
                    $canViewField,
                    $cityId,
                    $localizedName,
                    $regionId,
                    $search,
                ) {
                    if (! $canViewField($institution->visibility, 'name')) {
                        return collect();
                    }

                    $isHospital = $institution->categories->contains(
                        fn (Category $category): bool => $category->kind === 'hospital' || $category->parent?->kind === 'hospital',
                    );
                    $typeLabel = $isHospital ? 'Hospital' : 'Institution';

                    $categoryNames = $institution->categories
                        ->filter(fn (Category $category): bool => $canViewField($institution->visibility, 'category'))
                        ->map(fn (Category $category): string => $localizedName($category->parent).' / '.$localizedName($category))
                        ->filter()
                        ->values();

                    return $institution->locations
                        ->filter(function ($location) use ($canViewField, $cityId, $regionId): bool {
                            return $canViewField($location->visibility, 'address')
                                && ($cityId === null || $location->city_id === $cityId)
                                && ($regionId === null || $location->region_id === $regionId);
                        })
                        ->map(function ($location) use ($categoryNames, $institution, $localizedName, $search, $typeLabel): ?array {
                            $cityName = $localizedName($location->city);
                            $regionName = $localizedName($location->region);
                            $address = $location->address;
                            $searchableText = mb_strtolower(implode(' ', [
                                $institution->name,
                                $categoryNames->implode(' '),
                                $cityName,
                                $regionName,
                                $location->name,
                                $address,
                            ]));

                            if ($search !== '' && ! str_contains($searchableText, $search)) {
                                return null;
                            }

                            $nameParts = preg_split('/\s+/u', trim($institution->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                            return [
                                'id' => $institution->id,
                                'name' => $institution->name,
                                'initials' => collect($nameParts)->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->implode(''),
                                'specialty' => $categoryNames->implode(', '),
                                'city' => $cityName,
                                'region' => $regionName,
                                'address' => $address,
                                'services' => '',
                                'services_list' => [],
                                'weekly_schedule' => [],
                                'vacations' => [],
                                'approved_comments' => [],
                                'branch' => $location->name,
                                'type_label' => $typeLabel,
                            ];
                        })
                        ->filter();
                })
                ->values();
        }

        $profiles = $practitioners->concat($institutions)->sortBy('name')->values();

        $cities = City::query()
            ->where('is_active', true)
            ->with('contentTranslations.language')
            ->orderBy('name')
            ->get()
            ->each(function (City $city) use ($localizedName): void {
                $city->setAttribute('localized_name', $localizedName($city));
            });
        $specialties = Category::query()
            ->whereNotNull('parent_id')
            ->where('is_active', true)
            ->whereIn('kind', ['doctor', 'specialist', 'hospital'])
            ->with(['parent.contentTranslations.language', 'contentTranslations.language'])
            ->orderBy('name')
            ->get()
            ->each(function (Category $category) use ($localizedName): void {
                $category->setAttribute('localized_name', $localizedName($category));
                $category->setAttribute('parent_localized_name', $localizedName($category->parent));
            });
        $regions = Region::query()
            ->where('is_active', true)
            ->with(['city.contentTranslations.language', 'contentTranslations.language'])
            ->orderBy('name')
            ->get()
            ->each(function (Region $region) use ($localizedName): void {
                $region->setAttribute('localized_name', $localizedName($region));
                $region->setAttribute('city_localized_name', $localizedName($region->city));
            });
        $services = Service::query()
            ->where('is_active', true)
            ->with('contentTranslations.language')
            ->orderBy('name')
            ->get()
            ->each(function (Service $service) use ($localizedName): void {
                $service->setAttribute('localized_name', $localizedName($service));
            });

        return view('directory', [
            'practitioners' => $profiles,
            'search' => $filters['search'] ?? '',
            'city' => (string) ($filters['city'] ?? ''),
            'region' => (string) ($filters['region'] ?? ''),
            'specialty' => (string) ($filters['specialty'] ?? ''),
            'service' => (string) ($filters['service'] ?? ''),
            'type' => $type,
            'cities' => $cities,
            'specialties' => $specialties,
            'regions' => $regions,
            'services' => $services,
        ]);
    }
}
