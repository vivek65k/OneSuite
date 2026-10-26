<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use App\Models\Scopes\EmployeeDataScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback360 extends Model
{
    use HasFactory, ScopedByEmployee;

    protected $table = 'feedback_360s';

    public const RELATIONSHIPS = [
        'self' => 'Self',
        'manager' => 'Manager',
        'peer' => 'Peer',
        'subordinate' => 'Subordinate',
    ];

    protected $fillable = ['company_id', 'appraisal_id', 'reviewer_employee_id', 'relationship', 'rating', 'comments'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    /** Not limited by data access - the reviewer is often outside the viewer's own team. */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_employee_id')->withoutGlobalScope(EmployeeDataScope::class);
    }

    /** Feedback is about the employee being appraised, not the person who wrote it. */
    public function dataScopeVia(): ?string
    {
        return 'appraisal';
    }
}
