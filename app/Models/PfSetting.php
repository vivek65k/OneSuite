<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PfSetting extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'employee_contribution_percent', 'employer_contribution_percent',
        'wage_ceiling', 'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'employee_contribution_percent' => 'decimal:2',
            'employer_contribution_percent' => 'decimal:2',
            'wage_ceiling' => 'decimal:2',
            'is_enabled' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function forBranch(int $companyId, ?int $branchId): self
    {
        return static::firstOrCreate(['company_id' => $companyId, 'branch_id' => $branchId]);
    }
}
