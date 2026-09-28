<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['applicant_user_id', 'application_type', 'status', 'category_id', 'city_id', 'region_id', 'name', 'contact_email', 'phone', 'address', 'description', 'visibility', 'matched_practitioner_profile_id', 'matched_institution_id', 'matched_institution_location_id', 'reviewed_by', 'reviewed_at', 'admin_notes'])]
class ProfileApplication extends Model
{
    protected function casts(): array
    {
        return [
            'visibility' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function matchedPractitionerProfile(): BelongsTo
    {
        return $this->belongsTo(PractitionerProfile::class, 'matched_practitioner_profile_id');
    }

    public function matchedInstitution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'matched_institution_id');
    }

    public function matchedInstitutionLocation(): BelongsTo
    {
        return $this->belongsTo(InstitutionLocation::class, 'matched_institution_location_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }
}
