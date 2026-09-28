<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable([
    'user_id',
    'practitioner_profile_id',
    'service_id',
    'appointment_date',
    'starts_at',
    'ends_at',
    'status',
    'patient_notes',
    'doctor_notes',
])]
class Appointment extends Model
{
    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function practitionerProfile(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function comment(): HasOne
    {
        return $this->hasOne(AppointmentComment::class);
    }

    public function isCompleted(): bool
    {
        if ($this->status === 'completed') {
            return true;
        }

        if ($this->status !== 'approved') {
            return false;
        }

        $dateString = Carbon::parse($this->appointment_date)->toDateString();
        $endsAt = mb_substr($this->ends_at, 0, 5);

        return Carbon::parse($dateString.' '.$endsAt)->isPast();
    }

    public function canBeCommentedBy(?User $user): bool
    {
        if (! $user || $this->user_id !== $user->id) {
            return false;
        }

        if (in_array($this->status, ['cancelled', 'rejected'], true)) {
            return false;
        }

        if (! $this->isCompleted()) {
            return false;
        }

        return ! $this->comment()->exists();
    }
}
