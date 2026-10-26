<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use HasFactory;

    public const STATUSES = [
        'processed' => 'Processed',
        'paid' => 'Paid',
    ];

    public const MONTHS = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'month', 'year', 'status', 'run_date', 'processed_by_user_id',
        'employee_count', 'total_gross', 'total_deductions', 'total_net',
    ];

    protected function casts(): array
    {
        return [
            'run_date' => 'date',
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function monthLabel(): string
    {
        return (self::MONTHS[$this->month] ?? $this->month).' '.$this->year;
    }
}
