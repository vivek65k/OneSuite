<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSkill extends Model
{
    use ScopedByEmployee;

    public const PROFICIENCIES = ['beginner', 'intermediate', 'expert'];

    protected $fillable = ['employee_id', 'skill_name', 'proficiency'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
