<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumeBankEntry extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'name', 'email', 'phone', 'resume_path', 'skills', 'source', 'notes'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
