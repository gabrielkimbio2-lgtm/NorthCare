<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'slug', 'is_active'])]
class City extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
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
