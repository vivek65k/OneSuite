<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    protected $table = 'crm_notes';

    protected $fillable = ['company_id', 'notable_type', 'notable_id', 'body', 'file_path', 'file_name', 'owner_employee_id'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
