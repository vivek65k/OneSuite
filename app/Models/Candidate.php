<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    public const STAGES = ['applied', 'screening', 'interview', 'offer', 'hired', 'rejected'];

    protected $fillable = [
        'company_id', 'branch_id', 'job_position_id', 'name', 'email', 'phone',
        'resume_path', 'source', 'stage', 'applied_date', 'notes',
    ];

    protected function casts(): array
    {
        return ['applied_date' => 'date'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class)->orderByDesc('scheduled_at');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(OfferLetter::class);
    }
}
