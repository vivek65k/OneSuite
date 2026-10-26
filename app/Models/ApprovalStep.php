<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One level of a request's approval that has been acted on - see
 * App\Support\ApprovalFlow.
 */
class ApprovalStep extends Model
{
    protected $fillable = ['company_id', 'approvable_type', 'approvable_id', 'level', 'status', 'acted_by_user_id', 'on_behalf', 'remarks'];

    protected function casts(): array
    {
        return ['on_behalf' => 'boolean'];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }
}
