<?php

namespace App\Models\Crm;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmSetting extends Model
{
    protected $table = 'crm_settings';

    public const PIPELINE_MODES = ['shared' => 'One shared pipeline for all branches', 'per_branch' => 'Each branch configures its own pipeline'];

    protected $fillable = [
        'company_id', 'branch_id', 'default_currency', 'fiscal_year_start_month',
        'business_hours_start', 'business_hours_end', 'default_sla_policy_id', 'auto_assign_leads', 'pipeline_mode',
    ];

    protected $casts = ['auto_assign_leads' => 'boolean'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * The effective settings for a branch: its own override row if one
     * exists, otherwise the company-wide default (branch_id null) - so a
     * branch without explicit settings silently inherits the company's.
     */
    public static function effectiveFor(int $companyId, ?int $branchId): self
    {
        if ($branchId) {
            $branchRow = self::where('company_id', $companyId)->where('branch_id', $branchId)->first();
            if ($branchRow) {
                return $branchRow;
            }
        }

        return self::firstOrCreate(['company_id' => $companyId, 'branch_id' => null]);
    }
}
