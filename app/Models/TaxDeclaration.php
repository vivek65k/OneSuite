<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxDeclaration extends Model
{
    use HasFactory, ScopedByEmployee;

    public const REGIMES = [
        'old' => 'Old Regime',
        'new' => 'New Regime',
    ];

    protected $fillable = [
        'company_id', 'employee_id', 'financial_year', 'regime',
        'declared_investments', 'annual_tax', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'declared_investments' => 'decimal:2',
            'annual_tax' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function monthlyTds(): float
    {
        return round((float) $this->annual_tax / 12, 2);
    }

    public static function currentFinancialYear(?\DateTimeInterface $date = null): string
    {
        $date = $date ? \Illuminate\Support\Carbon::parse($date) : now();

        return $date->month >= 4
            ? $date->year.'-'.($date->year + 1)
            : ($date->year - 1).'-'.$date->year;
    }
}
