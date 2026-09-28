<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['practitioner_profile_id', 'day_of_week', 'starts_at', 'ends_at', 'is_active'])]
class AvailabilityWindow extends Model
{
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function practitionerProfile(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class);
    }
}
