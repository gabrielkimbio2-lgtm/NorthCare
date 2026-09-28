<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'category_id', 'city_id', 'region_id', 'name', 'contact_email', 'phone', 'address', 'bio', 'visibility', 'is_published'])]
class PractitionerProfile extends Model
{
    protected function casts(): array
    {
        return [
            'visibility' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot('duration_minutes')->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProfileApplication::class, 'matched_practitioner_profile_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AppointmentComment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(AppointmentComment::class)->where('status', 'approved');
    }

    public function getAverageDoctorRating(): ?float
    {
        $ratings = $this->approvedComments()
            ->whereNotNull('doctor_rating')
            ->pluck('doctor_rating');

        if ($ratings->isEmpty()) {
            return null;
        }

        return round($ratings->avg(), 1);
    }

    public function getTotalReviewsCount(): int
    {
        return $this->approvedComments()->count();
    }

    public function availabilityWindows(): HasMany
    {
        return $this->hasMany(AvailabilityWindow::class)->orderBy('day_of_week');
    }

    public function availabilityOverrides(): HasMany
    {
        return $this->hasMany(AvailabilityOverride::class)->orderBy('date')->orderBy('starts_at');
    }

    public function contentTranslations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }

    public function canViewField(string $field, bool $isRegistered): bool
    {
        $visibility = $this->visibility[$field] ?? 'public';

        return $visibility === 'public' || ($isRegistered && $visibility === 'registered');
    }

    /**
     * Generate available booking vacancy slots for a specific date and service duration.
     *
     * @return array<int, array{start: string, end: string, label: string, duration: int}>
     */
    public function generateSlotsForDate(\DateTimeInterface|string $date, ?int $durationMinutes = null): array
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeekIso; // 1 (Mon) to 7 (Sun)

        $window = $this->availabilityWindows->first(
            fn (AvailabilityWindow $w): bool => (int) $w->day_of_week === $dayOfWeek && $w->is_active,
        );

        if (! $window) {
            return [];
        }

        $dateString = $carbonDate->toDateString();
        $overrides = $this->availabilityOverrides->filter(
            fn (AvailabilityOverride $o): bool => Carbon::parse($o->date)->toDateString() === $dateString,
        );

        // If there is an all-day override (vacation/day off), no vacancy slots are available
        if ($overrides->contains(fn (AvailabilityOverride $o): bool => empty($o->starts_at) && empty($o->ends_at))) {
            return [];
        }

        // Closed timeslots from approved appointments on this day
        $approvedAppointments = $this->appointments->filter(
            fn (Appointment $a): bool => Carbon::parse($a->appointment_date)->toDateString() === $dateString && $a->status === 'approved',
        );

        $windowStart = $this->timeToMinutes($window->starts_at);
        $windowEnd = $this->timeToMinutes($window->ends_at);

        $slotDuration = max(15, $durationMinutes ?? 30);
        if ($slotDuration % 15 !== 0) {
            $slotDuration = (int) (round($slotDuration / 15) * 15);
        }

        $partialOverrides = $overrides
            ->filter(fn (AvailabilityOverride $o): bool => filled($o->starts_at) && filled($o->ends_at))
            ->map(fn (AvailabilityOverride $o): array => [
                'start' => $this->timeToMinutes($o->starts_at),
                'end' => $this->timeToMinutes($o->ends_at),
            ]);

        $appointmentBlocks = $approvedAppointments->map(fn (Appointment $a): array => [
            'start' => $this->timeToMinutes($a->starts_at),
            'end' => $this->timeToMinutes($a->ends_at),
        ]);

        $blockedTimes = $partialOverrides->concat($appointmentBlocks);

        $slots = [];
        $current = $windowStart;

        while ($current + $slotDuration <= $windowEnd) {
            $slotStart = $current;
            $slotEnd = $current + $slotDuration;

            $overlaps = $blockedTimes->contains(
                fn (array $block): bool => $slotStart < $block['end'] && $slotEnd > $block['start'],
            );

            if (! $overlaps) {
                $startFormatted = $this->minutesToTime($slotStart);
                $endFormatted = $this->minutesToTime($slotEnd);

                $slots[] = [
                    'start' => $startFormatted,
                    'end' => $endFormatted,
                    'label' => sprintf('%s - %s', $startFormatted, $endFormatted),
                    'duration' => $slotDuration,
                ];
            }

            $current += $slotDuration;
        }

        return $slots;
    }

    /**
     * Check if the practitioner has an all-day vacation/override on the given date.
     */
    public function hasVacationOnDate(\DateTimeInterface|string $date): bool
    {
        $dateString = Carbon::parse($date)->toDateString();

        return $this->availabilityOverrides->contains(
            fn (AvailabilityOverride $o): bool => Carbon::parse($o->date)->toDateString() === $dateString
                && empty($o->starts_at)
                && empty($o->ends_at),
        );
    }

    /**
     * Get a human-readable list of working days and times.
     *
     * @return array<int, string>
     */
    public function getWeeklyScheduleSummary(): array
    {
        $dayNames = [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
        ];

        return $this->availabilityWindows
            ->filter(fn (AvailabilityWindow $w): bool => $w->is_active)
            ->map(function (AvailabilityWindow $w) use ($dayNames): string {
                $day = $dayNames[$w->day_of_week] ?? ('Day '.$w->day_of_week);
                $start = mb_substr($w->starts_at, 0, 5);
                $end = mb_substr($w->ends_at, 0, 5);

                return sprintf('%s: %s - %s', $day, $start, $end);
            })
            ->values()
            ->all();
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    private function minutesToTime(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }
}
