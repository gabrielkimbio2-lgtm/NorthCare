<?php

namespace App\Http\Controllers\Practitioner;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityOverride;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $user->practitionerProfile()
            ->with([
                'services',
                'availabilityWindows',
                'availabilityOverrides' => fn ($query) => $query->whereDate('date', '>=', today())->orderBy('date')->orderBy('starts_at'),
            ])
            ->firstOrFail();

        $primaryService = $profile->services->first();
        $previewDuration = $primaryService?->pivot->duration_minutes ?? 30;

        $upcomingVacancyDays = [];
        for ($i = 0; $i < 7; $i++) {
            $dayDate = today()->addDays($i);
            $daySlots = $profile->generateSlotsForDate($dayDate, $previewDuration);
            $isOnVacation = $profile->hasVacationOnDate($dayDate);
            $upcomingVacancyDays[] = [
                'date' => $dayDate,
                'formatted_date' => $dayDate->format('D, M d'),
                'is_today' => $i === 0,
                'is_vacation' => $isOnVacation,
                'slots' => $daySlots,
            ];
        }

        return view('practitioner.profile.edit', [
            'profile' => $profile,
            'services' => Service::query()->where('is_active', true)->orderBy('name')->get(),
            'suggestions' => $user->serviceSuggestions()->latest()->limit(10)->get(),
            'windowsByDay' => $profile->availabilityWindows->keyBy('day_of_week'),
            'upcomingVacancyDays' => $upcomingVacancyDays,
            'previewDuration' => $previewDuration,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:4000'],
            'visibility' => ['required', 'array:name,contact_email,phone,address,bio'],
            'visibility.name' => ['required', Rule::in(['public', 'registered', 'private'])],
            'visibility.contact_email' => ['required', Rule::in(['public', 'registered', 'private'])],
            'visibility.phone' => ['required', Rule::in(['public', 'registered', 'private'])],
            'visibility.address' => ['required', Rule::in(['public', 'registered', 'private'])],
            'visibility.bio' => ['required', Rule::in(['public', 'registered', 'private'])],
        ]);

        $request->user()->practitionerProfile()->firstOrFail()->update($validated);

        return back()->with('status', 'Profile details and visibility saved.');
    }

    public function updateServices(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'services' => ['nullable', 'array', 'max:30'],
            'services.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
            'service_durations' => ['nullable', 'array'],
            'service_durations.*' => ['required', 'integer', Rule::in(range(15, 240, 15))],
        ]);

        $serviceDurations = $validated['service_durations'] ?? [];
        $serviceSync = [];

        foreach ($validated['services'] ?? [] as $serviceId) {
            if (! isset($serviceDurations[$serviceId])) {
                throw ValidationException::withMessages(['service_durations.'.$serviceId => 'Choose a duration for every selected service.']);
            }

            $serviceSync[$serviceId] = ['duration_minutes' => (int) $serviceDurations[$serviceId]];
        }

        $request->user()->practitionerProfile()->firstOrFail()->services()->sync($serviceSync);

        return back()->with('status', 'Services updated.');
    }

    public function updateAvailability(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'windows' => ['required', 'array', 'max:7'],
            'windows.*.day_of_week' => ['required', 'integer', 'distinct', 'between:1,7'],
            'windows.*.enabled' => ['nullable', 'boolean'],
            'windows.*.starts_at' => ['nullable', 'date_format:H:i'],
            'windows.*.ends_at' => ['nullable', 'date_format:H:i'],
        ]);
        $profile = $request->user()->practitionerProfile()->firstOrFail();

        foreach ($validated['windows'] as $index => $window) {
            $dayOfWeek = (int) $window['day_of_week'];

            if (! filter_var($window['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $profile->availabilityWindows()->where('day_of_week', $dayOfWeek)->delete();

                continue;
            }

            if (empty($window['starts_at']) || empty($window['ends_at'])) {
                throw ValidationException::withMessages([
                    'windows.'.$index.'.starts_at' => 'Choose both a start and end time for each available day.',
                ]);
            }

            $startMinutes = $this->minutesAfterMidnight($window['starts_at']);
            $endMinutes = $this->minutesAfterMidnight($window['ends_at']);

            if ($startMinutes >= $endMinutes || $startMinutes % 15 !== 0 || $endMinutes % 15 !== 0) {
                throw ValidationException::withMessages([
                    'windows.'.$index.'.starts_at' => 'Availability times must be increasing and aligned to 15-minute intervals.',
                ]);
            }

            $profile->availabilityWindows()->updateOrCreate(
                ['day_of_week' => $dayOfWeek],
                [
                    'starts_at' => $window['starts_at'],
                    'ends_at' => $window['ends_at'],
                    'is_active' => true,
                ],
            );
        }

        return back()->with('status', 'Weekly availability saved.');
    }

    public function storeUnavailableTime(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:160'],
        ]);
        $hasStart = filled($validated['starts_at'] ?? null);
        $hasEnd = filled($validated['ends_at'] ?? null);

        if ($hasStart !== $hasEnd) {
            throw ValidationException::withMessages(['starts_at' => 'Choose both times, or leave both blank to mark the full day unavailable.']);
        }

        if ($hasStart) {
            $startMinutes = $this->minutesAfterMidnight($validated['starts_at']);
            $endMinutes = $this->minutesAfterMidnight($validated['ends_at']);

            if ($startMinutes >= $endMinutes || $startMinutes % 15 !== 0 || $endMinutes % 15 !== 0) {
                throw ValidationException::withMessages(['starts_at' => 'Unavailable times must be increasing and aligned to 15-minute intervals.']);
            }
        }

        $profile = $request->user()->practitionerProfile()->firstOrFail();
        $startDate = Carbon::parse($validated['date']);
        $endDate = filled($validated['end_date'] ?? null) ? Carbon::parse($validated['end_date']) : $startDate;

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $profile->availabilityOverrides()->create([
                'date' => $current->toDateString(),
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'reason' => $validated['reason'] ?? null,
            ]);
            $current->addDay();
        }

        return back()->with('status', 'Unavailable time / vacation saved.');
    }

    public function destroyUnavailableTime(Request $request, AvailabilityOverride $availabilityOverride): RedirectResponse
    {
        $request->user()->practitionerProfile()->firstOrFail()
            ->availabilityOverrides()
            ->whereKey($availabilityOverride->id)
            ->firstOrFail()
            ->delete();

        return back()->with('status', 'Unavailable time removed.');
    }

    private function minutesAfterMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
