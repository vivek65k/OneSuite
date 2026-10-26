<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePolicySetting extends Model
{
    /** Recommended starting values shown before the policy is first saved. */
    public const RECOMMENDED = [
        'exclude_weekends' => true,
        'exclude_holidays' => true,
        'sandwich_rule' => false,
        'allow_half_day' => true,
        'min_notice_days' => 0,
        'max_backdated_days' => 30,
        'max_consecutive_days' => null,
    ];

    /** How leave was counted before any policy existed: every calendar day, no limits. */
    public const LEGACY = [
        'exclude_weekends' => false,
        'exclude_holidays' => false,
        'sandwich_rule' => false,
        'allow_half_day' => false,
        'min_notice_days' => 0,
        'max_backdated_days' => null,
        'max_consecutive_days' => null,
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'exclude_weekends', 'exclude_holidays', 'sandwich_rule',
        'allow_half_day', 'min_notice_days', 'max_backdated_days', 'max_consecutive_days',
    ];

    protected function casts(): array
    {
        return [
            'exclude_weekends' => 'boolean',
            'exclude_holidays' => 'boolean',
            'sandwich_rule' => 'boolean',
            'allow_half_day' => 'boolean',
            'min_notice_days' => 'integer',
            'max_backdated_days' => 'integer',
            'max_consecutive_days' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
