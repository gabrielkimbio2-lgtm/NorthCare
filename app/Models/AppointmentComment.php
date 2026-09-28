<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'appointment_id',
    'user_id',
    'practitioner_profile_id',
    'comment',
    'doctor_rating',
    'service_rating',
    'status',
    'admin_notes',
    'reviewed_by',
    'reviewed_at',
    'is_reported',
    'report_reason',
    'reported_at',
])]
class AppointmentComment extends Model
{
    protected function casts(): array
    {
        return [
            'doctor_rating' => 'integer',
            'service_rating' => 'integer',
            'is_reported' => 'boolean',
            'reviewed_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function practitionerProfile(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getAverageRating(): ?float
    {
        if ($this->doctor_rating === null && $this->service_rating === null) {
            return null;
        }

        $ratings = array_filter([$this->doctor_rating, $this->service_rating], fn ($r) => $r !== null);

        return count($ratings) > 0 ? array_sum($ratings) / count($ratings) : null;
    }

    public function getRatingStars(?int $rating): string
    {
        if ($rating === null) {
            return 'N/A';
        }

        return str_repeat('★', $rating).str_repeat('☆', 5 - $rating);
    }
}
