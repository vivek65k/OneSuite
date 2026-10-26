<?php

namespace App\Services\Crm;

use App\Models\Crm\Activity;
use App\Models\Crm\Workflow;
use App\Models\Crm\WorkflowExecution;
use Throwable;

/**
 * Runs a Workflow's steps against a target record and logs what happened.
 * Steps that can act on real data (create_task) do so; steps that would
 * need a real email/notification provider are logged as simulated rather
 * than silently faked as sent, so the execution history stays honest.
 */
class WorkflowExecutor
{
    public function run(Workflow $workflow, ?string $contextType, ?int $contextId, ?string $contextLabel, string $triggerType = 'manual'): WorkflowExecution
    {
        $startedAt = now();
        $log = [];
        $status = 'success';
        $errorMessage = null;

        try {
            foreach ($workflow->steps as $step) {
                $log[] = $this->runStep($step, $contextType, $contextId);
            }
        } catch (Throwable $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
        }

        return WorkflowExecution::create([
            'company_id' => $workflow->company_id,
            'workflow_id' => $workflow->id,
            'status' => $status,
            'trigger_type' => $triggerType,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'context_label' => $contextLabel,
            'steps_log' => $log,
            'error_message' => $errorMessage,
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);
    }

    protected function runStep($step, ?string $contextType, ?int $contextId): array
    {
        $base = ['step_id' => $step->id, 'step_type' => $step->step_type, 'summary' => $step->summary];

        return match ($step->step_type) {
            'create_task' => $this->createTask($step, $contextType, $contextId) + $base,
            'send_email', 'send_notification' => $base + ['outcome' => 'simulated', 'message' => 'Simulated - no live email/notification provider is connected in this environment.'],
            'wait' => $base + ['outcome' => 'skipped', 'message' => 'Wait step - no delay applied during manual execution.'],
            'update_field' => $base + ['outcome' => 'simulated', 'message' => 'Simulated - field updates require entity-specific configuration.'],
            default => $base + ['outcome' => 'skipped', 'message' => 'Unknown step type.'],
        };
    }

    protected function createTask($step, ?string $contextType, ?int $contextId): array
    {
        if (! $contextType || ! $contextId) {
            return ['outcome' => 'skipped', 'message' => 'No target record provided - task not created.'];
        }

        $task = Activity::create([
            'company_id' => \App\Models\Company::current()->id,
            'type' => 'task',
            'subject' => $step->summary ?: 'Workflow follow-up task',
            'related_type' => $contextType,
            'related_id' => $contextId,
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => now()->addDay(),
        ]);

        return ['outcome' => 'success', 'message' => "Created task #{$task->id}."];
    }
}
