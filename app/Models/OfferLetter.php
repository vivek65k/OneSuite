<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferLetter extends Model
{
    public const STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'withdrawn'];

    protected $fillable = [
        'candidate_id', 'job_position_id', 'salary_offered', 'currency',
        'status', 'offer_date', 'joining_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'salary_offered' => 'decimal:2',
            'offer_date' => 'date',
            'joining_date' => 'date',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }
}
