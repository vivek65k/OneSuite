<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    protected $fillable = ['workflow_id', 'name', 'approver_role', 'sequence'];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
