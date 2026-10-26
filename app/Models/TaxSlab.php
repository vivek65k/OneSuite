<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSlab extends Model
{
    use HasFactory;

    public const REGIMES = [
        'old' => 'Old Regime',
        'new' => 'New Regime',
    ];

    protected $fillable = ['company_id', 'branch_id', 'regime', 'financial_year', 'min_income', 'max_income', 'tax_rate_percent'];

    protected function casts(): array
    {
        return [
            'min_income' => 'decimal:2',
            'max_income' => 'decimal:2',
            'tax_rate_percent' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
