<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalSettlement extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'pending' => 'Pending',
        'processed' => 'Processed',
        'paid' => 'Paid',
    ];

    protected $fillable = [
        'company_id', 'resignation_id', 'employee_id', 'gross_dues', 'deductions',
        'net_payable', 'status', 'processed_by_user_id', 'processed_at', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'gross_dues' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net_payable' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function resignation(): BelongsTo
    {
        return $this->belongsTo(Resignation::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
