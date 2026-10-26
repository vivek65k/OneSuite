<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\Crm\ResolvesRelatable;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Workflow;
use App\Models\Crm\WorkflowExecution;
use App\Models\Crm\WorkflowStep;
use App\Services\Crm\WorkflowExecutor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    use ResolvesRelatable;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.workflows.index', [
            'workflows' => Workflow::where('company_id', $companyId)->withCount(['steps', 'executions'])->orderByDesc('created_at')->get(),
        ]);
    }

    public function run(Request $request, Workflow $workflow): RedirectResponse
    {
        abort_unless($workflow->company_id === Company::current()->id, 404);

        $data = $request->validate(['relatable' => ['nullable', 'string']]);
        $relatable = $data['relatable'] ?? null;

        $execution = (new WorkflowExecutor)->run(
            $workflow,
            $relatable ? $this->relatedTypeFromKey($relatable) : null,
            $relatable ? $this->relatedIdFromKey($relatable) : null,
            $relatable ? $this->relatedLabel($this->relatedTypeFromKey($relatable), $this->relatedIdFromKey($relatable)) : null,
            'manual'
        );

        $message = $execution->status === 'success' ? 'Workflow ran successfully.' : "Workflow failed: {$execution->error_message}";

        return redirect()->route('admin.crm.workflows.executions', $workflow)->with('status', $message);
    }

    public function executions(Workflow $workflow): View
    {
        abort_unless($workflow->company_id === Company::current()->id, 404);

        return view('admin.crm.workflows.executions', [
            'workflow' => $workflow,
            'executions' => $workflow->executions()->paginate(20),
            'relatable' => $this->relatableOptions(),
        ]);
    }

    public function failedJobs(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.workflows.failed-jobs', [
            'executions' => WorkflowExecution::where('company_id', $companyId)->where('status', 'failed')
                ->with('workflow')->orderByDesc('created_at')->paginate(20),
        ]);
    }

    public function retry(WorkflowExecution $workflowExecution): RedirectResponse
    {
        abort_unless($workflowExecution->company_id === Company::current()->id, 404);

        $execution = (new WorkflowExecutor)->run(
            $workflowExecution->workflow,
            $workflowExecution->context_type,
            $workflowExecution->context_id,
            $workflowExecution->context_label,
            'retry'
        );

        return back()->with('status', $execution->status === 'success' ? 'Retry succeeded.' : "Retry failed again: {$execution->error_message}");
    }

    public function create(): View
    {
        return view('admin.crm.workflows.form', [
            'workflow' => new Workflow,
            'steps' => collect(),
            'stepTypes' => WorkflowStep::STEP_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $workflow = Workflow::create(['name' => $data['name'], 'trigger_event' => $data['trigger_event'], 'company_id' => Company::current()->id, 'is_active' => true]);
        $this->saveSteps($workflow, $data['steps']);

        return redirect()->route('admin.crm.workflows.edit', $workflow)->with('status', 'Workflow created.');
    }

    public function edit(Workflow $workflow): View
    {
        abort_unless($workflow->company_id === Company::current()->id, 404);

        return view('admin.crm.workflows.form', [
            'workflow' => $workflow,
            'steps' => $workflow->steps,
            'stepTypes' => WorkflowStep::STEP_TYPES,
        ]);
    }

    public function update(Request $request, Workflow $workflow): RedirectResponse
    {
        abort_unless($workflow->company_id === Company::current()->id, 404);
        $data = $this->validated($request);
        $workflow->update(['name' => $data['name'], 'trigger_event' => $data['trigger_event']]);
        $workflow->steps()->delete();
        $this->saveSteps($workflow, $data['steps']);

        return redirect()->route('admin.crm.workflows.edit', $workflow)->with('status', 'Workflow updated.');
    }

    public function toggle(Workflow $workflow): RedirectResponse
    {
        abort_unless($workflow->company_id === Company::current()->id, 404);
        $workflow->update(['is_active' => ! $workflow->is_active]);

        return back()->with('status', $workflow->is_active ? 'Workflow activated.' : 'Workflow paused.');
    }

    public function destroy(Workflow $workflow): RedirectResponse
    {
        abort_unless($workflow->company_id === Company::current()->id, 404);
        $workflow->delete();

        return redirect()->route('admin.crm.workflows.index')->with('status', 'Workflow deleted.');
    }

    protected function validated(Request $request): array
    {
        if ($request->filled('steps_json') && is_string($request->input('steps_json'))) {
            $request->merge(['steps' => json_decode($request->input('steps_json'), true) ?? []]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_event' => ['nullable', 'string'],
            'steps' => ['nullable', 'array'],
            'steps.*.step_type' => ['required_with:steps', 'string'],
            'steps.*.summary' => ['nullable', 'string'],
        ]) + ['steps' => $request->input('steps', [])];
    }

    protected function saveSteps(Workflow $workflow, array $steps): void
    {
        foreach ($steps as $i => $step) {
            WorkflowStep::create([
                'workflow_id' => $workflow->id,
                'sort_order' => $i,
                'step_type' => $step['step_type'],
                'summary' => $step['summary'] ?? null,
            ]);
        }
    }
}
