<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAsset extends Model
{
    use ScopedByEmployee;

    public const CATEGORIES = [
        'laptop' => 'Laptop',
        'mobile' => 'Mobile',
        'id_card' => 'ID Card',
        'sim_card' => 'SIM Card',
        'uniform' => 'Uniform',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'assigned' => 'Assigned',
        'returned' => 'Returned',
    ];

    protected $fillable = [
        'company_id', 'employee_id', 'category', 'asset_name', 'asset_tag', 'identifier', 'brand_model',
        'assigned_date', 'returned_date', 'status', 'condition_at_return', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'returned_date' => 'date',
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
