<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['city_id', 'name', 'slug', 'is_active'])]
class Region extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function practitionerProfiles(): HasMany
    {
        return $this->hasMany(PractitionerProfile::class);
    }

    public function institutionLocations(): HasMany
    {
        return $this->hasMany(InstitutionLocation::class);
    }

    public function contentTranslations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }
}
