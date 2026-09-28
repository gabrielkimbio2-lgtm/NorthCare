<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['practitioner_profile_id', 'date', 'starts_at', 'ends_at', 'reason'])]
class AvailabilityOverride extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function practitionerProfile(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class);
    }
}
