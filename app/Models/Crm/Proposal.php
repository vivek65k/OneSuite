<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Proposal extends Model
{
    protected $table = 'crm_proposals';

    public const STATUSES = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
    ];

    protected $fillable = ['company_id', 'quotation_id', 'account_id', 'title', 'content', 'status', 'owner_employee_id', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function signatureRequests(): MorphMany
    {
        return $this->morphMany(ESignatureRequest::class, 'signable');
    }
}
