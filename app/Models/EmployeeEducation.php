<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEducation extends Model
{
    use ScopedByEmployee;

    protected $table = 'employee_educations';

    protected $fillable = ['employee_id', 'qualification', 'institution', 'field_of_study', 'start_year', 'end_year', 'grade'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
