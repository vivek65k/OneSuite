<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFamilyMember extends Model
{
    use ScopedByEmployee;

    protected $fillable = ['employee_id', 'name', 'relation', 'date_of_birth', 'phone'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
