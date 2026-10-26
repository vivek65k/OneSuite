<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TravelRequest extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public const MODES = [
        'flight' => 'Flight',
        'train' => 'Train',
        'bus' => 'Bus',
        'car' => 'Car',
        'other' => 'Other',
    ];

    protected $fillable = [
        'company_id', 'employee_id', 'purpose', 'from_location', 'to_location',
        'start_date', 'end_date', 'mode_of_travel', 'estimated_cost',
        'status', 'reviewed_by_user_id', 'reviewed_at', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'estimated_cost' => 'decimal:2',
            'reviewed_at' => 'datetime',
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

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TravelBooking::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(TravelExpense::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(TravelSettlement::class);
    }
}
