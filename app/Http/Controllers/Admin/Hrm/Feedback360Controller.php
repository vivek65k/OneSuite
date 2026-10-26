<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Feedback360;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class Feedback360Controller extends Controller
{
    /** People who have left can't be recorded as giving new feedback. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Feedback360::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'tab' => array_key_exists($text('tab'), Feedback360::RELATIONSHIPS) ? $text('tab') : '',
            'employee_id' => $id('employee_id'),
            'cycle_id' => $id('cycle_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->whereHas('appraisal', fn ($a) => $a->where('employee_id', $employeeId)))
            ->when($state['cycle_id'], fn ($q, $cycleId) => $q->whereHas('appraisal', fn ($a) => $a->where('appraisal_cycle_id', $cycleId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $named = fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like));
                $q->where(fn ($w) => $w->whereHas('appraisal.employee', $named)->orWhereHas('reviewer', $named));
            });

        $feedback = $filtered()
            ->when($state['tab'], fn ($q, $relationship) => $q->where('relationship', $relationship))
            ->with(['appraisal.employee.department:id,name', 'appraisal.appraisalCycle:id,name', 'reviewer:id,name,employee_code,avatar'])
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'appraisal_id', 'reviewer_employee_id', 'relationship', 'rating']);
        $appraisals = $this->appraisalQuery()->with(['employee:id,name,employee_code,avatar,manager_id', 'appraisalCycle:id,name'])->get()
            ->filter(fn (Appraisal $a) => $a->employee && $a->appraisalCycle)
            ->sortBy(fn (Appraisal $a) => mb_strtolower($a->employee->name))->values();
        $open = $appraisals->where('status', 'draft');
        $withFeedback = $all->pluck('appraisal_id')->unique();
        $byRelationship = $filtered()->selectRaw('relationship, COUNT(*) as total')->groupBy('relationship')->pluck('total', 'relationship');
        $people = $this->employeeQuery()->orderBy('name')->get(['id', 'name', 'employee_code', 'manager_id', 'status']);
        $canCreate = $request->user()->can('create', Feedback360::class);

        return view('admin.hrm.performance.feedback-360.index', [
            'feedback' => $feedback,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'cycles' => AppraisalCycle::query()->where('company_id', Company::current()->id)->orderByDesc('period_start')->pluck('name', 'id'),
            'counts' => ['all' => (int) $byRelationship->sum()] + collect(Feedback360::RELATIONSHIPS)->map(fn ($label, $key) => (int) ($byRelationship[$key] ?? 0))->all(),
            'kpis' => [
                'total' => $all->count(),
                'average' => $all->isNotEmpty() ? round((float) $all->avg('rating'), 1) : null,
                'open' => $open->count(),
                'covered' => $open->whereIn('id', $withFeedback)->count(),
                'waiting' => $open->whereNotIn('id', $withFeedback)->count(),
                'reviewers' => $all->pluck('reviewer_employee_id')->unique()->count(),
            ],
            'canCreate' => $canCreate,
            // Open appraisals feedback can still be added to, with what has been given so far.
            'openAppraisals' => $open->values(),
            'formData' => $canCreate ? [
                'appraisals' => $open->mapWithKeys(fn (Appraisal $a) => [$a->id => [
                    'employee_id' => $a->employee_id,
                    'name' => $a->employee->name,
                    'cycle' => $a->appraisalCycle->name,
                    'manager_id' => $a->employee->manager_id,
                    'given' => $all->where('appraisal_id', $a->id)->map(fn (Feedback360 $f) => ['reviewer' => $f->reviewer_employee_id, 'relationship' => $f->relationship, 'rating' => (int) $f->rating])->values(),
                ]]),
                'people' => $people->whereNotIn('status', self::INACTIVE)->mapWithKeys(fn (Employee $e) => [$e->id => ['name' => $e->name, 'manager_id' => $e->manager_id]]),
            ] : [],
            'reviewers' => $people->whereNotIn('status', self::INACTIVE)->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Feedback360::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'appraisal_id' => ['required', 'integer'],
            'reviewer_employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'relationship' => ['required', Rule::in(array_keys(Feedback360::RELATIONSHIPS))],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ], [
            'rating.required' => 'Pick a rating from 1 to 5.',
        ]);

        // The appraisal must be one this login can see, not just any in the company.
        $appraisal = $this->appraisalQuery()->with('employee:id,name')->whereKey($data['appraisal_id'])->first();

        if (! $appraisal || ! $appraisal->employee) {
            throw ValidationException::withMessages(['appraisal_id' => 'Choose an appraisal from the list.']);
        }

        if ($appraisal->status !== 'draft') {
            throw ValidationException::withMessages(['appraisal_id' => $appraisal->employee->name.'\'s appraisal is already completed, so feedback can no longer be added. Reopen the appraisal first.']);
        }

        $reviewer = Employee::findOrFail($data['reviewer_employee_id']);

        if (in_array($reviewer->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['reviewer_employee_id' => $reviewer->name.' is no longer with the company.']);
        }

        $isSelf = (int) $reviewer->id === (int) $appraisal->employee_id;

        // "Self" means the person reviewing themselves, and nothing else does.
        if ($isSelf && $data['relationship'] !== 'self') {
            throw ValidationException::withMessages(['relationship' => $reviewer->name.' is the person being appraised, so the relationship must be "Self".']);
        }
        if (! $isSelf && $data['relationship'] === 'self') {
            throw ValidationException::withMessages(['relationship' => '"Self" feedback can only come from '.$appraisal->employee->name.'. Pick how '.$reviewer->name.' works with them instead.']);
        }

        if (Feedback360::query()->where('appraisal_id', $appraisal->id)->where('reviewer_employee_id', $reviewer->id)->exists()) {
            throw ValidationException::withMessages(['reviewer_employee_id' => $reviewer->name.' has already given feedback on this appraisal. Remove that entry first if it needs changing.']);
        }

        Feedback360::create([
            'company_id' => $companyId,
            'appraisal_id' => $appraisal->id,
            'reviewer_employee_id' => $reviewer->id,
            'relationship' => $data['relationship'],
            'rating' => (int) $data['rating'],
            'comments' => filled($data['comments'] ?? null) ? trim($data['comments']) : null,
        ]);

        return back()->with('status', 'Feedback from '.$reviewer->name.' recorded for '.$appraisal->employee->name.'.');
    }

    public function destroy(Feedback360 $feedback360): RedirectResponse
    {
        abort_unless($this->scoped()->whereKey($feedback360->id)->exists(), 404);
        $this->authorize('delete', $feedback360);

        // Once the appraisal is signed off, the feedback behind it is part of the record.
        if ($feedback360->appraisal?->status === 'completed') {
            return back()->withErrors(['feedback' => 'That appraisal is completed, so its feedback can\'t be removed. Reopen the appraisal first.']);
        }

        $feedback360->delete();

        return back()->with('status', 'Feedback removed.');
    }

    /** Feedback this login may see: its own company and, when tied to a branch, appraisals of that branch's employees. */
    protected function scoped(): Builder
    {
        return Feedback360::query()
            ->where('feedback_360s.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('appraisal.employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function appraisalQuery(): Builder
    {
        return Appraisal::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
