<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    protected $table = 'crm_workflow_steps';

    public const STEP_TYPES = [
        'send_email' => 'Send Email',
        'create_task' => 'Create Task',
        'update_field' => 'Update Field',
        'wait' => 'Wait',
        'send_notification' => 'Send Notification',
    ];

    protected $fillable = ['workflow_id', 'sort_order', 'step_type', 'summary', 'config'];

    protected $casts = ['config' => 'array'];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }
}
