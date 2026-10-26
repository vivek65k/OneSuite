<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowExecution extends Model
{
    protected $table = 'crm_workflow_executions';

    public const STATUSES = ['success' => 'Success', 'failed' => 'Failed'];

    protected $fillable = [
        'company_id', 'workflow_id', 'status', 'trigger_type',
        'context_type', 'context_id', 'context_label',
        'steps_log', 'error_message', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'steps_log' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    public function getDurationMsAttribute(): ?int
    {
        return $this->started_at && $this->finished_at
            ? $this->started_at->diffInMilliseconds($this->finished_at)
            : null;
    }
}
