<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSequenceEnrollment extends Model
{
    protected $table = 'crm_email_sequence_enrollments';

    public const STATUSES = ['active' => 'Active', 'completed' => 'Completed', 'stopped' => 'Stopped'];

    protected $fillable = [
        'company_id', 'sequence_id', 'lead_id', 'contact_id', 'current_step',
        'status', 'next_step_due_at', 'enrolled_at',
    ];

    protected $casts = ['next_step_due_at' => 'datetime', 'enrolled_at' => 'datetime'];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(EmailSequence::class, 'sequence_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function getRecipientNameAttribute(): ?string
    {
        return $this->lead?->name ?? $this->contact?->name;
    }
}
