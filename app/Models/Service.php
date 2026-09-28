<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'slug', 'description', 'is_active'])]
class Service extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function practitionerProfiles(): BelongsToMany
    {
        return $this->belongsToMany(PractitionerProfile::class)->withPivot('duration_minutes')->withTimestamps();
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(ServiceSuggestion::class);
    }

    public function contentTranslations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AppointmentComment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(AppointmentComment::class)->where('status', 'approved');
    }

    public function getAverageServiceRating(): ?float
    {
        $ratings = $this->approvedComments()
            ->whereNotNull('service_rating')
            ->pluck('service_rating');

        if ($ratings->isEmpty()) {
            return null;
        }

        return round($ratings->avg(), 1);
    }

    public function getTotalReviewsCount(): int
    {
        return $this->approvedComments()->count();
    }
}
