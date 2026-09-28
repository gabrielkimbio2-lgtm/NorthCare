<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['profile_application_id', 'storage_path', 'original_name', 'mime_type', 'byte_size'])]
class ApplicationDocument extends Model
{
    public function application(): BelongsTo
    {
        return $this->belongsTo(ProfileApplication::class, 'profile_application_id');
    }
}
