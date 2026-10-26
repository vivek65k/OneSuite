<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmployeeAsset;
use App\Models\Loan;
use App\Models\Resignation;
use App\Models\SeparationClearance;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClearanceController extends Controller
{
    protected const TABS = ['open', 'ready', 'closed'];

    /** How many finished or withdrawn separations the "Closed" tab keeps on screen. */
    protected const CLOSED_LIMIT = 50;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SeparationClearance::class);

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        // Links from the old page used ?status=pending|cleared.
        $tab = ['pending' => 'open', 'cleared' => 'ready'][$text('status')] ?? $text('tab');
        $state = [
            'tab' => in_array($tab, self::TABS, true) ? $tab : '',
            'waiting' => array_key_exists($text('waiting'), SeparationClearance::DEPARTMENTS) ? $text('waiting') : '',
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $all = $this->rows();
        $needle = mb_strtolower($state['search']);
        $filtered = $all
            ->when($state['waiting'] !== '', fn (Collection $c) => $c->filter(fn ($r) => $r['items']->contains(fn ($i) => $i->department === $state['waiting'] && ! $i->is_cleared)))
            ->when($needle !== '', fn (Collection $c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r['name'].' '.$r['resignation']->employee?->employee_code), $needle)))
            ->values();
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'open' => $rows->where('stage', 'open'),
            'ready' => $rows->where('stage', 'ready'),
            'closed' => $rows->where('stage', 'closed')->sortByDesc(fn ($r) => $r['resignation']->last_working_day)->take(self::CLOSED_LIMIT),
            default => $rows->where('stage', '!=', 'closed'),
        };
        $live = $tabbed($all, '');
        $pending = $live->flatMap(fn ($r) => $r['items']->where('is_cleared', false));
        $slowest = $pending->countBy('department')->sortDesc();
        $user = $request->user();

        return view('admin.hrm.separation.clearances', [
            'rows' => $tabbed($filtered, $state['tab'])->values(),
            'state' => $state,
            'counts' => ['all' => $tabbed($filtered, '')->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($filtered, $tab)->count()])->all(),
            'kpis' => [
                'people' => $live->count(),
                'pending' => $pending->count(),
                'items' => $live->sum('total'),
                'slowest' => $slowest->isNotEmpty() ? [SeparationClearance::DEPARTMENTS[$slowest->keys()->first()] ?? $slowest->keys()->first(), $slowest->first()] : null,
                'ready' => $live->where('stage', 'ready')->count(),
                'ready_now' => $live->where('stage', 'ready')->where('days_left', '<=', 0)->count(),
                'late' => $live->where('stage', 'open')->where('days_left', '<', 0)->count(),
            ],
            'canUpdate' => $user->can('update', new SeparationClearance),
            // This login can't sign off their own exit (super admin excepted), same as denyOwnRequest().
            'ownEmployeeId' => $user->is_super_admin ? null : $all->pluck('resignation')->first(fn (Resignation $r) => (int) $r->employee?->user_id === (int) $user->id)?->employee_id,
        ]);
    }

    public function toggle(Request $request, SeparationClearance $clearance): RedirectResponse
    {
        abort_unless($this->scoped()->whereKey($clearance->id)->exists(), 404);
        $this->authorize('update', $clearance);
        $this->denyOwnRequest($clearance->employee);

        $data = $request->validate([
            // What the button asked for. Without it the item simply flips, as the old button did.
            'cleared' => ['nullable', 'in:0,1'],
            'remarks' => ['nullable', 'string', 'max:300'],
        ]);
        $note = filled($data['remarks'] ?? null) ? trim($data['remarks']) : null;
        $label = SeparationClearance::DEPARTMENTS[$clearance->department] ?? $clearance->department;

        $done = DB::transaction(function () use ($clearance, $data, $note) {
            $row = SeparationClearance::withoutGlobalScopes()->whereKey($clearance->id)->lockForUpdate()->first();
            $this->ensureOpen(Resignation::withoutGlobalScopes()->find($row->resignation_id));

            $want = isset($data['cleared']) ? (bool) $data['cleared'] : ! $row->is_cleared;
            // Someone else already did it (or the button was pressed twice): leave their sign-off alone.
            if ($want === (bool) $row->is_cleared) {
                return null;
            }
            $this->apply($row, $want, $note);

            return $want;
        });
        $who = $clearance->employee?->name ?? 'the employee';

        return back()->with('status', match ($done) {
            null => $label.' clearance for '.$who.' was already '.($clearance->fresh()->is_cleared ? 'cleared' : 'open').'; nothing changed.',
            true => $label.' clearance for '.$who.' marked cleared.'.$this->remaining($clearance->resignation_id),
            false => $label.' clearance for '.$who.' reopened.',
        });
    }

    /** Sign off every open item of one person in one go. */
    public function clearAll(Request $request, Resignation $resignation): RedirectResponse
    {
        abort_unless($this->scoped()->where('resignation_id', $resignation->id)->exists(), 404);
        $this->authorize('update', new SeparationClearance);
        $this->denyOwnRequest($resignation->employee);

        $count = DB::transaction(function () use ($resignation) {
            $this->ensureOpen(Resignation::withoutGlobalScopes()->whereKey($resignation->id)->lockForUpdate()->first());
            $open = SeparationClearance::withoutGlobalScopes()->where('resignation_id', $resignation->id)->where('is_cleared', false)->lockForUpdate()->get();
            $open->each(fn (SeparationClearance $row) => $this->apply($row, true, null));

            return $open->count();
        });
        $who = $resignation->employee?->name ?? 'the employee';

        return back()->with('status', $count
            ? $count.' clearance '.($count === 1 ? 'item' : 'items').' for '.$who.' marked cleared. The separation can be completed once the last working day arrives.'
            : 'Everything was already cleared for '.$who.'; nothing changed.');
    }

    /** A checklist is only live while the person is serving notice; afterwards it is the record of what was signed off. */
    protected function ensureOpen(?Resignation $resignation): void
    {
        if ($resignation?->status !== 'accepted') {
            throw ValidationException::withMessages(['clearance' => 'This resignation is '.($resignation?->status ?? 'gone').', so its clearance checklist can no longer be changed.']);
        }
    }

    protected function apply(SeparationClearance $row, bool $cleared, ?string $note): void
    {
        $by = Auth::user()?->name ?? 'HR';
        $row->forceFill($cleared
            ? ['is_cleared' => true, 'cleared_by_user_id' => Auth::id(), 'cleared_at' => now(), 'remarks' => $note]
            // Kept on the record: a sign-off was taken back, by whom and why.
            : ['is_cleared' => false, 'cleared_by_user_id' => null, 'cleared_at' => null, 'remarks' => 'Reopened by '.$by.' on '.today()->format('M j, Y').($note ? ': '.$note : '.')]
        )->save();
    }

    protected function remaining(int $resignationId): string
    {
        $left = SeparationClearance::withoutGlobalScopes()->where('resignation_id', $resignationId)->where('is_cleared', false)->count();

        return $left ? ' '.$left.' '.($left === 1 ? 'item' : 'items').' still open.' : ' Everything is cleared now.';
    }

    /** One row per person with a checklist: their items in a fixed order, progress, and what each department should look at. */
    protected function rows(): Collection
    {
        $order = array_flip(array_keys(SeparationClearance::DEPARTMENTS));
        $resignations = Resignation::query()
            ->whereIn('id', $this->scoped()->select('resignation_id'))
            ->with(['employee.department:id,name', 'clearances.clearedBy:id,name', 'exitInterview:id,resignation_id', 'finalSettlement:id,resignation_id,status'])
            ->orderBy('last_working_day')->orderBy('id')->get();
        $employeeIds = $resignations->pluck('employee_id');
        $assets = EmployeeAsset::withoutGlobalScopes()->whereIn('employee_id', $employeeIds)->where('status', 'assigned')
            ->selectRaw('employee_id, count(*) as n')->groupBy('employee_id')->pluck('n', 'employee_id');
        $loans = Loan::withoutGlobalScopes()->whereIn('employee_id', $employeeIds)->where('status', 'active')
            ->selectRaw('employee_id, sum(outstanding_amount) as due')->groupBy('employee_id')->pluck('due', 'employee_id');

        return $resignations->map(function (Resignation $r) use ($order, $assets, $loans) {
            $items = $r->clearances->sortBy(fn ($i) => $order[$i->department] ?? 99)->values();
            $done = $items->where('is_cleared', true)->count();
            $out = (int) ($assets[$r->employee_id] ?? 0);
            $due = (float) ($loans[$r->employee_id] ?? 0);

            return [
                'resignation' => $r,
                'name' => $r->employee?->name ?? 'Removed employee',
                'items' => $items,
                'done' => $done,
                'total' => $items->count(),
                'days_left' => (int) today()->diffInDays($r->last_working_day, false),
                'stage' => $r->status !== 'accepted' ? 'closed' : ($done === $items->count() ? 'ready' : 'open'),
                // What stands in the way of each sign-off, taken from the other modules. A guide, not a block.
                'hints' => array_filter([
                    'it' => $out ? ['bad', $out.' '.($out === 1 ? 'asset' : 'assets').' not returned', 'assets'] : ['good', 'No company assets with them', null],
                    'admin' => $out ? ['bad', $out.' '.($out === 1 ? 'asset' : 'assets').' not returned', 'assets'] : null,
                    'finance' => $due > 0 ? ['bad', 'Loan balance of '.number_format($due).' still owed', null] : ['good', 'No loan balance', null],
                    'hr' => $r->exitInterview ? ['good', 'Exit interview recorded', null] : ['warn', 'Exit interview not recorded yet', 'interview'],
                ]),
            ];
        });
    }

    /** Clearance items this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return SeparationClearance::query()
            ->where('separation_clearances.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }
}
