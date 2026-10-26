<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'code', 'days_per_year',
        'is_paid', 'carry_forward', 'max_carry_forward_days', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_per_year' => 'decimal:2',
            'max_carry_forward_days' => 'decimal:2',
            'is_paid' => 'boolean',
            'carry_forward' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
