<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'native_name', 'is_default', 'is_active'])]
class Language extends Model
{
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function uiTranslations(): HasMany
    {
        return $this->hasMany(UiTranslation::class);
    }

    public function contentTranslations(): HasMany
    {
        return $this->hasMany(ContentTranslation::class);
    }
}
