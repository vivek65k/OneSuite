<?php

namespace App\Models\Crm;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contact extends Model
{
    protected $table = 'crm_contacts';

    protected $fillable = [
        'company_id', 'account_id', 'name', 'job_title', 'email', 'phone', 'company_name',
        'owner_employee_id', 'customer_segment_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }

    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class, 'converted_contact_id');
    }

    /**
     * Contacts this one has a defined relationship to (either direction) -
     * a simple undirected pairing, so both sides see each other without
     * needing two mirrored rows per pair.
     */
    public function relatedContacts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'crm_contact_relationships', 'contact_id', 'related_contact_id')
            ->withPivot('relationship_type', 'id')
            ->withTimestamps();
    }

    public function relatedByContacts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'crm_contact_relationships', 'related_contact_id', 'contact_id')
            ->withPivot('relationship_type', 'id')
            ->withTimestamps();
    }
}
