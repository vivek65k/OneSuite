<?php

namespace App\Models\Crm;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactRelationship extends Model
{
    protected $table = 'crm_contact_relationships';

    public const TYPES = [
        'reports_to' => 'Reports to',
        'colleague' => 'Colleague',
        'assistant' => 'Assistant',
        'spouse' => 'Spouse',
        'referred_by' => 'Referred by',
        'other' => 'Other',
    ];

    protected $fillable = ['company_id', 'contact_id', 'related_contact_id', 'relationship_type'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function relatedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'related_contact_id');
    }
}
