<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'generated' => 'Generated',
        'paid' => 'Paid',
    ];

    protected $fillable = [
        'company_id', 'payroll_run_id', 'employee_id',
        'gross_earnings', 'pf_employee_amount', 'pf_employer_amount',
        'esi_employee_amount', 'esi_employer_amount', 'tax_amount',
        'loan_deduction_amount', 'other_deduction_amount', 'total_deductions',
        'net_pay', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_earnings' => 'decimal:2',
            'pf_employee_amount' => 'decimal:2',
            'pf_employer_amount' => 'decimal:2',
            'esi_employee_amount' => 'decimal:2',
            'esi_employer_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'loan_deduction_amount' => 'decimal:2',
            'other_deduction_amount' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }

    /** Net pay spelled out for the printed payslip, e.g. "Two lakh ten thousand only". Null when intl is unavailable. */
    public function netPayInWords(?string $currency = null): ?string
    {
        if (! class_exists(\NumberFormatter::class)) {
            return null;
        }

        $amount = round((float) $this->net_pay, 2);
        $whole = (int) floor($amount);
        $fraction = (int) round(($amount - $whole) * 100);
        $speller = new \NumberFormatter(strtoupper((string) $currency) === 'INR' ? 'en_IN' : 'en', \NumberFormatter::SPELLOUT);
        $words = $speller->format($whole);

        if ($words === false) {
            return null;
        }

        return ucfirst($words).($fraction > 0 ? ' and '.sprintf('%02d', $fraction).'/100' : '').' only';
    }
}
