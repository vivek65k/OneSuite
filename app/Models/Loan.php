<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'active' => 'Active',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'company_id', 'employee_id', 'loan_type', 'principal_amount', 'monthly_installment',
        'outstanding_amount', 'disbursed_date', 'status', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'monthly_installment' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
            'disbursed_date' => 'date',
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
}
