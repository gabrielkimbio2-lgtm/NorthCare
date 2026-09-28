<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['institution_id', 'city_id', 'region_id', 'name', 'address', 'contact_email', 'phone', 'visibility', 'is_published'])]
class InstitutionLocation extends Model
{
    protected function casts(): array
    {
        return [
            'visibility' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProfileApplication::class, 'matched_institution_location_id');
    }

    public function contentTranslations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }
}
