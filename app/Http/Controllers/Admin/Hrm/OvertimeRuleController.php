<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRule;
use App\Models\Shift;
use App\Support\Attendance;
use App\Support\BranchContext;
use App\Support\ShiftTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OvertimeRuleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', OvertimeRule::class);

        $state = $request->only(['search', 'status']);
        $all = $this->scopedQuery()->with('shift')->orderBy('name')->get();

        $rules = $all
            ->when(($state['status'] ?? '') === 'active', fn ($c) => $c->where('is_active', true))
            ->when(($state['status'] ?? '') === 'inactive', fn ($c) => $c->where('is_active', false))
            ->when(($state['status'] ?? '') === 'general', fn ($c) => $c->whereNull('shift_id'))
            ->when($state['search'] ?? null, fn ($c, $search) => $c->filter(fn ($r) => str_contains(mb_strtolower($r->name.' '.$r->shift?->name.' '.$r->shift?->code), mb_strtolower($search))))
            ->values();

        // Which rule actually applies to each active shift: its own rule wins over the general one.
        $shifts = $this->shifts()->where('is_active', true)->values();
        $general = $all->first(fn ($r) => $r->is_active && ! $r->shift_id);
        $coverage = $shifts->map(fn ($shift) => [
            'shift' => $shift,
            'rule' => $all->first(fn ($r) => $r->is_active && (int) $r->shift_id === $shift->id) ?? $general,
            'own' => $all->contains(fn ($r) => $r->is_active && (int) $r->shift_id === $shift->id),
        ]);
        $active = $all->where('is_active', true);

        return view('admin.hrm.shift.overtime-rules.index', [
            'rules' => $rules,
            'state' => $state,
            'coverage' => $coverage,
            'counts' => [
                'all' => $all->count(),
                'active' => $active->count(),
                'inactive' => $all->where('is_active', false)->count(),
                'general' => $all->whereNull('shift_id')->count(),
            ],
            'kpis' => [
                'covered' => $coverage->filter(fn ($c) => $c['rule'])->count(),
                'shifts' => $shifts->count(),
                'top_multiplier' => $active->max('multiplier'),
                'ot_hours' => (float) Attendance::requests(OvertimeRequest::class)
                    ->where('status', 'approved')
                    ->whereDate('date', '>=', now()->startOfMonth())
                    ->sum('hours'),
                'ot_pending' => Attendance::requests(OvertimeRequest::class)->where('status', 'pending')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', OvertimeRule::class);

        return view('admin.hrm.shift.overtime-rules.create', $this->formData(new OvertimeRule(['multiplier' => 1.5, 'is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', OvertimeRule::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        $rule = OvertimeRule::create($data);

        return redirect()->route('admin.overtime-rules.index')->with('status', "Overtime rule “{$rule->name}” added.");
    }

    public function edit(OvertimeRule $overtimeRule): View
    {
        $this->authorize('update', $overtimeRule);
        $this->ensureOwn($overtimeRule);

        return view('admin.hrm.shift.overtime-rules.edit', $this->formData($overtimeRule));
    }

    public function update(Request $request, OvertimeRule $overtimeRule): RedirectResponse
    {
        $this->authorize('update', $overtimeRule);
        $this->ensureOwn($overtimeRule);

        $overtimeRule->update($this->validated($request, $overtimeRule));

        return redirect()->route('admin.overtime-rules.index')->with('status', "Overtime rule “{$overtimeRule->name}” updated.");
    }

    public function toggle(OvertimeRule $overtimeRule): RedirectResponse
    {
        $this->authorize('update', $overtimeRule);
        $this->ensureOwn($overtimeRule);

        if (! $overtimeRule->is_active && ($clash = $this->clash($overtimeRule->branch_id, $overtimeRule->shift_id, $overtimeRule->id))) {
            return back()->withErrors(['rule' => "Can't activate “{$overtimeRule->name}”: “{$clash->name}” already covers ".($overtimeRule->shift?->name ?? 'all shifts').'. Deactivate that one first.']);
        }

        $overtimeRule->update(['is_active' => ! $overtimeRule->is_active]);

        return back()->with('status', "“{$overtimeRule->name}” ".($overtimeRule->is_active ? 'activated.' : 'deactivated.'));
    }

    public function destroy(OvertimeRule $overtimeRule): RedirectResponse
    {
        $this->authorize('delete', $overtimeRule);
        $this->ensureOwn($overtimeRule);

        $overtimeRule->delete();

        return redirect()->route('admin.overtime-rules.index')->with('status', "Overtime rule “{$overtimeRule->name}” deleted.");
    }

    protected function ensureOwn(OvertimeRule $rule): void
    {
        abort_unless((int) $rule->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $rule->branch_id !== (int) BranchContext::currentBranchId(), 404);
    }

    protected function validated(Request $request, ?OvertimeRule $rule = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'shift_id' => ['nullable', 'integer'],
            'daily_threshold_hours' => ['required', 'numeric', 'min:1', 'max:23.75'],
            'multiplier' => ['required', 'numeric', 'min:1', 'max:10'],
        ], [
            'daily_threshold_hours.min' => 'The daily threshold must be at least 1 hour - below that every working hour would be overtime.',
        ]);

        // Only this company's shifts, from the branch being managed.
        $shift = null;
        if ($data['shift_id'] ?? null) {
            $shift = $this->shifts()->firstWhere('id', (int) $data['shift_id']);
            if (! $shift) {
                throw ValidationException::withMessages(['shift_id' => 'That shift isn\'t available here.']);
            }
        }

        $data['shift_id'] = $shift?->id;
        $data['branch_id'] = $shift?->branch_id ?? $rule?->branch_id ?? BranchContext::currentBranchId();
        $data['is_active'] = $request->boolean('is_active');

        // One active rule per shift (and one general rule), so it's always clear which applies.
        if ($data['is_active'] && ($clash = $this->clash($data['branch_id'], $data['shift_id'], $rule?->id))) {
            throw ValidationException::withMessages(['shift_id' => "“{$clash->name}” is already the active rule for ".($shift?->name ?? 'all shifts').'. Deactivate it first, or save this one as inactive.']);
        }

        return $data;
    }

    /** Another active rule with the same scope (branch + shift, or branch-wide), if any. */
    protected function clash(?int $branchId, ?int $shiftId, ?int $ignoreId): ?OvertimeRule
    {
        return OvertimeRule::query()
            ->where('company_id', Company::current()->id)
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId), fn ($q) => $q->whereNull('branch_id'))
            ->when($shiftId, fn ($q) => $q->where('shift_id', $shiftId), fn ($q) => $q->whereNull('shift_id'))
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->first();
    }

    protected function formData(OvertimeRule $rule): array
    {
        $others = $this->scopedQuery()->where('is_active', true)->when($rule->exists, fn ($q) => $q->whereKeyNot($rule->id))->get(['id', 'name', 'shift_id']);

        return [
            'overtimeRule' => $rule,
            'shifts' => $this->shifts(),
            'taken' => $others->mapWithKeys(fn ($r) => [(string) ($r->shift_id ?? 'all') => $r->name]),
        ];
    }

    protected function scopedQuery()
    {
        return OvertimeRule::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    protected function shifts()
    {
        return Shift::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('start_time')
            ->get();
    }
}
