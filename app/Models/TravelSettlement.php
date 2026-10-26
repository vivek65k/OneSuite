<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelSettlement extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'pending' => 'Pending',
        'settled' => 'Settled',
    ];

    protected $fillable = [
        'company_id', 'travel_request_id', 'employee_id', 'total_booking_cost',
        'total_expense_amount', 'reimbursement_amount', 'status', 'settled_date', 'settled_by_user_id', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'total_booking_cost' => 'decimal:2',
            'total_expense_amount' => 'decimal:2',
            'reimbursement_amount' => 'decimal:2',
            'settled_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function travelRequest(): BelongsTo
    {
        return $this->belongsTo(TravelRequest::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by_user_id');
    }
}
