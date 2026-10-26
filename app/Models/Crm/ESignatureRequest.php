<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ESignatureRequest extends Model
{
    protected $table = 'crm_esignature_requests';

    public const STATUSES = [
        'pending' => 'Pending',
        'signed' => 'Signed',
        'declined' => 'Declined',
    ];

    protected $fillable = ['company_id', 'signable_type', 'signable_id', 'signer_name', 'signer_email', 'status', 'sent_at', 'signed_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'signed_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }
}
