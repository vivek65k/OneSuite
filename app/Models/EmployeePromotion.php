<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePromotion extends Model
{
    use ScopedByEmployee;

    protected $fillable = ['employee_id', 'from_designation', 'to_designation', 'promotion_date', 'notes'];

    protected function casts(): array
    {
        return ['promotion_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
