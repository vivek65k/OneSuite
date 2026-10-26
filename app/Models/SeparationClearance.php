<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeparationClearance extends Model
{
    use HasFactory, ScopedByEmployee;

    public const DEPARTMENTS = [
        'it' => 'IT',
        'finance' => 'Finance',
        'admin' => 'Admin',
        'hr' => 'HR',
        'manager' => 'Manager',
    ];

    protected $fillable = [
        'company_id', 'resignation_id', 'employee_id', 'department',
        'is_cleared', 'cleared_by_user_id', 'cleared_at', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'is_cleared' => 'boolean',
            'cleared_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function resignation(): BelongsTo
    {
        return $this->belongsTo(Resignation::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function clearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by_user_id');
    }
}
