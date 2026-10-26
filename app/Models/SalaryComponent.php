<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponent extends Model
{
    use HasFactory;

    public const TYPES = [
        'earning' => 'Earning',
        'deduction' => 'Deduction',
    ];

    protected $fillable = ['company_id', 'branch_id', 'name', 'code', 'type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * First-run convenience: give a branch a starter set of earning
     * components so the Salary Structure screen isn't empty before anyone
     * has configured anything. Runs once per branch, not once per company -
     * each branch configures its own salary components independently.
     */
    public static function seedDefaults(int $companyId, ?int $branchId): void
    {
        if (static::where('company_id', $companyId)->where('branch_id', $branchId)->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'Basic', 'code' => 'BASIC'],
            ['name' => 'House Rent Allowance', 'code' => 'HRA'],
            ['name' => 'Conveyance Allowance', 'code' => 'CONVEYANCE'],
            ['name' => 'Medical Allowance', 'code' => 'MEDICAL'],
            ['name' => 'Special Allowance', 'code' => 'SPECIAL'],
        ];

        foreach ($defaults as $default) {
            static::create($default + ['company_id' => $companyId, 'branch_id' => $branchId, 'type' => 'earning', 'is_active' => true]);
        }
    }
}
