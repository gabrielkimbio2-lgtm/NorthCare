<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['submitted_by', 'name', 'description', 'status', 'service_id', 'reviewed_by', 'reviewed_at', 'review_notes'])]
class ServiceSuggestion extends Model
{
    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
