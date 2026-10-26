<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Support\BranchContext;
use App\Support\ShiftTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Shift::class);

        $state = $request->only(['search', 'status']);
        $all = $this->scopedQuery()->withCount('overtimeRules')->orderBy('start_time')->get();

        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();
        $ids = $all->pluck('id');
        $thisWeek = ShiftRoster::query()->whereIn('shift_id', $ids)->whereDate('date', '>=', $weekStart)->whereDate('date', '<=', $weekEnd)
            ->toBase()->selectRaw('shift_id, COUNT(DISTINCT employee_id) as employees')->groupBy('shift_id')->pluck('employees', 'shift_id');
        $upcoming = ShiftRoster::query()->whereIn('shift_id', $ids)->whereDate('date', '>=', now()->toDateString())
            ->toBase()->selectRaw('shift_id, COUNT(*) as aggregate')->groupBy('shift_id')->pluck('aggregate', 'shift_id');
        $totalRosters = ShiftRoster::query()->whereIn('shift_id', $ids)
            ->toBase()->selectRaw('shift_id, COUNT(*) as aggregate')->groupBy('shift_id')->pluck('aggregate', 'shift_id');

        $shifts = $all
            ->when($state['search'] ?? null, fn ($c, $search) => $c->filter(fn ($s) => str_contains(mb_strtolower($s->name.' '.$s->code), mb_strtolower($search))))
            ->when($state['status'] ?? null, fn ($c, $status) => match ($status) {
                'active' => $c->where('is_active', true),
                'inactive' => $c->where('is_active', false),
                'night' => $c->where('is_night_shift', true),
                'flexible' => $c->where('is_flexible', true),
                default => $c,
            })
            ->values();

        return view('admin.hrm.shift.shifts.index', [
            'shifts' => $shifts,
            'coverage' => $all->where('is_active', true)->values(),
            'state' => $state,
            'counts' => [
                'all' => $all->count(),
                'active' => $all->where('is_active', true)->count(),
                'inactive' => $all->where('is_active', false)->count(),
                'night' => $all->where('is_night_shift', true)->count(),
                'flexible' => $all->where('is_flexible', true)->count(),
            ],
            'thisWeek' => $thisWeek,
            'upcoming' => $upcoming,
            'totalRosters' => $totalRosters,
            'rosteredThisWeek' => ShiftRoster::query()->whereIn('shift_id', $ids)->whereDate('date', '>=', $weekStart)->whereDate('date', '<=', $weekEnd)->distinct()->count('employee_id'),
            'branchNames' => BranchContext::isRestricted() ? collect() : Branch::query()->where('company_id', Company::current()->id)->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Shift::class);

        return view('admin.hrm.shift.shifts.create', [
            'shift' => new Shift(),
            'existingCodes' => $this->scopedQuery()->pluck('code')->map(fn ($c) => strtoupper($c))->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Shift::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;
        $data['branch_id'] = BranchContext::currentBranchId();

        Shift::create($data);

        return redirect()->route('admin.shifts.index')->with('status', 'Shift added.');
    }

    public function edit(Shift $shift): View
    {
        $this->authorize('update', $shift);
        $this->ensureOwn($shift);

        return view('admin.hrm.shift.shifts.edit', [
            'shift' => $shift,
            'existingCodes' => $this->scopedQuery()->where('branch_id', $shift->branch_id)->whereKeyNot($shift->id)->pluck('code')->map(fn ($c) => strtoupper($c))->values(),
            'usage' => $this->usage($shift),
        ]);
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $this->authorize('update', $shift);
        $this->ensureOwn($shift);

        $shift->update($this->validated($request, $shift));

        return redirect()->route('admin.shifts.index')->with('status', 'Shift updated.');
    }

    /** One-click activate / deactivate from the card. */
    public function toggle(Shift $shift): RedirectResponse
    {
        $this->authorize('update', $shift);
        $this->ensureOwn($shift);

        $shift->update(['is_active' => ! $shift->is_active]);

        return back()->with('status', "{$shift->name} ".($shift->is_active ? 'activated.' : 'deactivated - it can no longer be assigned.'));
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $this->authorize('delete', $shift);
        $this->ensureOwn($shift);

        // shift_rosters cascade on delete - deleting a used shift would wipe its schedule history.
        $usage = $this->usage($shift);
        if ($usage['rosters']) {
            return back()->withErrors(['shift' => "{$shift->name} can't be deleted: it has {$usage['rosters']} roster ".($usage['rosters'] === 1 ? 'assignment' : 'assignments').' that would be erased. Deactivate it instead.']);
        }

        $shift->delete();

        return redirect()->route('admin.shifts.index')->with('status', 'Shift deleted.');
    }

    protected function scopedQuery()
    {
        return Shift::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    /** @return array{rosters: int, upcoming: int, overtime_rules: int} */
    protected function usage(Shift $shift): array
    {
        return [
            'rosters' => ShiftRoster::where('shift_id', $shift->id)->count(),
            'upcoming' => ShiftRoster::where('shift_id', $shift->id)->whereDate('date', '>=', now()->toDateString())->count(),
            'overtime_rules' => $shift->overtimeRules()->count(),
        ];
    }

    /** The policy only checks permissions - also require the current company (and branch). */
    protected function ensureOwn(Shift $shift): void
    {
        abort_unless((int) $shift->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $shift->branch_id !== (int) BranchContext::currentBranchId(), 404);
    }

    protected function validated(Request $request, ?Shift $shift = null): array
    {
        $companyId = Company::current()->id;

        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                // Unique within the shift's own branch (not whichever branch the editor is in).
                Rule::unique('shifts', 'code')->where('company_id', $companyId)->where('branch_id', $shift?->branch_id ?? BranchContext::currentBranchId())->ignore($shift?->id),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:600'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'half_day_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'full_day_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'flexible_window_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
        ], [
            'end_time.different' => 'The shift must end at a different time than it starts.',
        ]);

        $data['is_night_shift'] = $request->boolean('is_night_shift');
        $data['is_flexible'] = $request->boolean('is_flexible');
        $data['is_active'] = $request->boolean('is_active');
        $data += ['half_day_hours' => null, 'full_day_hours' => null, 'flexible_window_minutes' => null];

        $span = ShiftTime::spanMinutes($data['start_time'], $data['end_time']);
        $errors = [];
        if ((int) $data['break_minutes'] >= $span) {
            $errors['break_minutes'] = 'The break is as long as the whole shift.';
        }
        if (ShiftTime::crossesMidnight($data['start_time'], $data['end_time']) && ! $data['is_night_shift']) {
            // Ending "earlier" than it starts means it runs past midnight - that's a night shift.
            $data['is_night_shift'] = true;
        }
        if ($data['half_day_hours'] !== null && $data['full_day_hours'] !== null && (float) $data['half_day_hours'] > (float) $data['full_day_hours']) {
            $errors['half_day_hours'] = 'Half-day hours can\'t be more than full-day hours.';
        }
        if ($data['full_day_hours'] !== null && (float) $data['full_day_hours'] * 60 > $span) {
            $errors['full_day_hours'] = 'Full-day hours are longer than the shift itself ('.round($span / 60, 2).'h).';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        if (! $data['is_flexible']) {
            $data['flexible_window_minutes'] = null;
        }

        return $data;
    }
}
