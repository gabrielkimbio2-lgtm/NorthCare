<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['user_id', 'name', 'contact_email', 'phone', 'description', 'visibility', 'is_published'])]
class Institution extends Model
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

    public function locations(): HasMany
    {
        return $this->hasMany(InstitutionLocation::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProfileApplication::class, 'matched_institution_id');
    }

    public function contentTranslations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }
}
