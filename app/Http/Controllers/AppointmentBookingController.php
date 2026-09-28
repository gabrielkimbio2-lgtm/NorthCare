<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\PractitionerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentBookingController extends Controller
{
    public function create(Request $request, PractitionerProfile $practitioner): View
    {
        abort_unless($practitioner->is_published, 404);

        $practitioner->load([
            'services' => fn ($query) => $query->where('is_active', true),
            'availabilityWindows' => fn ($query) => $query->where('is_active', true),
            'availabilityOverrides' => fn ($query) => $query->whereDate('date', '>=', today()),
            'appointments' => fn ($query) => $query->whereDate('appointment_date', '>=', today()),
            'category.contentTranslations.language',
            'city.contentTranslations.language',
            'region.contentTranslations.language',
        ]);

        // Handle case where practitioner has no services
        if ($practitioner->services->isEmpty()) {
            return view('appointments.create', [
                'practitioner' => $practitioner,
                'services' => collect(),
                'selectedService' => null,
                'selectedDate' => today(),
                'availableSlots' => [],
                'isOnVacation' => false,
                'daysPreview' => [],
                'slotDuration' => 30,
                'noServices' => true,
            ]);
        }

        $selectedServiceId = (int) $request->query('service_id', $practitioner->services->first()?->id ?? 0);
        $selectedService = $practitioner->services->firstWhere('id', $selectedServiceId) ?? $practitioner->services->first();
        $slotDuration = $selectedService?->pivot->duration_minutes ?? 30;

        $selectedDate = $request->query('date') ? Carbon::parse($request->query('date')) : today();
        if ($selectedDate->isPast() && ! $selectedDate->isToday()) {
            $selectedDate = today();
        }

        $availableSlots = $practitioner->generateSlotsForDate($selectedDate, $slotDuration);
        $isOnVacation = $practitioner->hasVacationOnDate($selectedDate);

        // Next 14 days preview
        $daysPreview = [];
        for ($i = 0; $i < 14; $i++) {
            $date = today()->addDays($i);
            $hasWindows = $practitioner->availabilityWindows->contains('day_of_week', $date->dayOfWeekIso);
            $vacation = $practitioner->hasVacationOnDate($date);

            $daysPreview[] = [
                'date' => $date->toDateString(),
                'day_name' => $date->format('D'),
                'day_num' => $date->format('d'),
                'month' => $date->format('M'),
                'is_selected' => $date->isSameDay($selectedDate),
                'is_working' => $hasWindows && ! $vacation,
            ];
        }

        return view('appointments.create', [
            'practitioner' => $practitioner,
            'services' => $practitioner->services,
            'selectedService' => $selectedService,
            'selectedDate' => $selectedDate,
            'availableSlots' => $availableSlots,
            'isOnVacation' => $isOnVacation,
            'daysPreview' => $daysPreview,
            'slotDuration' => $slotDuration,
            'noServices' => false,
        ]);
    }

    public function store(Request $request, PractitionerProfile $practitioner): RedirectResponse
    {
        abort_unless($practitioner->is_published, 404);

        $validated = $request->validate([
            'service_id' => [
                'required',
                'integer',
                Rule::exists('practitioner_profile_service', 'service_id')
                    ->where('practitioner_profile_id', $practitioner->id),
            ],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'starts_at' => ['required', 'date_format:H:i'],
            'patient_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service = $practitioner->services()->whereKey($validated['service_id'])->firstOrFail();
        $duration = $service->pivot->duration_minutes ?? 30;

        $startParts = array_map('intval', explode(':', $validated['starts_at']));
        $startMinutes = ($startParts[0] * 60) + $startParts[1];
        $endMinutes = $startMinutes + $duration;
        $endsAt = sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);

        // Verify slot is available
        $availableSlots = $practitioner->generateSlotsForDate($validated['appointment_date'], $duration);
        $slotExists = collect($availableSlots)->contains(fn (array $slot): bool => $slot['start'] === $validated['starts_at']);

        if (! $slotExists) {
            throw ValidationException::withMessages([
                'starts_at' => 'The selected timeslot is no longer available. Please select another time.',
            ]);
        }

        // Check if patient already has another approved appointment at that time
        $patientConflict = Appointment::query()
            ->where('user_id', $request->user()->id)
            ->where('appointment_date', $validated['appointment_date'])
            ->where('status', 'approved')
            ->where(function ($query) use ($validated, $endsAt) {
                $query->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $validated['starts_at']);
            })
            ->exists();

        if ($patientConflict) {
            throw ValidationException::withMessages([
                'starts_at' => 'You already have another approved appointment scheduled at this time.',
            ]);
        }

        Appointment::create([
            'user_id' => $request->user()->id,
            'practitioner_profile_id' => $practitioner->id,
            'service_id' => $service->id,
            'appointment_date' => $validated['appointment_date'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $endsAt,
            'status' => 'pending',
            'patient_notes' => $validated['patient_notes'] ?? null,
        ]);

        return redirect()->route('dashboard')->with('status', 'Appointment request submitted. Awaiting doctor approval.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $isPatient = $appointment->user_id === $request->user()->id;
        $isDoctor = $request->user()->practitionerProfile?->id === $appointment->practitioner_profile_id;

        abort_unless($isPatient || $isDoctor || $request->user()->isAdmin(), 403);

        $appointment->update(['status' => 'cancelled']);

        return back()->with('status', 'Appointment has been cancelled.');
    }
}
