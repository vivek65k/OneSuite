<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\ExitInterview;
use App\Models\Resignation;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExitInterviewController extends Controller
{
    protected const TABS = ['due', 'done', 'missed'];

    /** A resignation that is neither accepted nor completed has nobody leaving on it. */
    protected const LEAVING = ['accepted', 'completed'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ExitInterview::class);

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'department_id' => ctype_digit($text('department_id')) ? (int) $text('department_id') : null,
            'rehire' => in_array($text('rehire'), ['yes', 'no'], true) ? $text('rehire') : '',
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $all = $this->scoped()->whereIn('status', self::LEAVING)
            ->with(['employee.department:id,name', 'exitInterview.conductedBy:id,name'])
            // People still here first (soonest to leave on top), then those who have left, latest first.
            ->orderByRaw("status = 'accepted' desc")->orderByRaw("case when status = 'accepted' then last_working_day end asc")->orderByDesc('last_working_day')->orderByDesc('id')
            ->get();
        $needle = mb_strtolower($state['search']);
        $filtered = $all
            ->when($state['department_id'], fn (Collection $c, $departmentId) => $c->filter(fn (Resignation $r) => (int) $r->employee?->department_id === $departmentId))
            ->when($state['rehire'] !== '', fn (Collection $c) => $c->filter(fn (Resignation $r) => $r->exitInterview && $r->exitInterview->would_rehire === ($state['rehire'] === 'yes')))
            ->when($needle !== '', fn (Collection $c) => $c->filter(fn (Resignation $r) => str_contains(mb_strtolower(($r->employee?->name ?? '').' '.$r->employee?->employee_code.' '.$r->exitInterview?->feedback), $needle)))
            ->values();
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'due' => $rows->filter(fn (Resignation $r) => ! $r->exitInterview && $r->status === 'accepted'),
            'done' => $rows->filter(fn (Resignation $r) => (bool) $r->exitInterview),
            'missed' => $rows->filter(fn (Resignation $r) => ! $r->exitInterview && $r->status === 'completed'),
            default => $rows,
        };
        $interviews = $all->pluck('exitInterview')->filter();
        $rated = $interviews->whereNotNull('rating');
        $asked = $interviews->whereNotNull('would_rehire');
        $due = $tabbed($all, 'due');
        $user = $request->user();

        return view('admin.hrm.separation.exit-interviews', [
            'resignations' => $tabbed($filtered, $state['tab'])->values(),
            'state' => $state,
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $all->map(fn (Resignation $r) => $r->employee?->department_id)->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'counts' => ['all' => $filtered->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($filtered, $tab)->count()])->all(),
            'kpis' => [
                'due' => $due->count(),
                'due_week' => $due->filter(fn (Resignation $r) => $r->last_working_day->lte(today()->addDays(7)))->count(),
                'done' => $interviews->count(),
                'leavers' => $all->count(),
                'missed' => $tabbed($all, 'missed')->count(),
                'rating' => $rated->isNotEmpty() ? round($rated->avg('rating'), 1) : null,
                'rated' => $rated->count(),
                'low' => $rated->where('rating', '<=', 2)->count(),
                'rehire' => $asked->isNotEmpty() ? (int) round($asked->where('would_rehire', true)->count() / $asked->count() * 100) : null,
                'asked' => $asked->count(),
                'rehire_yes' => $asked->where('would_rehire', true)->count(),
            ],
            'canCreate' => $user->can('create', ExitInterview::class),
            'canUpdate' => $user->can('update', new ExitInterview),
            'canDelete' => $user->can('delete', new ExitInterview),
            // Nobody records their own exit interview (super admin excepted), same as denyOwnRequest().
            'ownEmployeeId' => $user->is_super_admin ? null : $all->first(fn (Resignation $r) => (int) $r->employee?->user_id === (int) $user->id)?->employee_id,
        ]);
    }

    /** Record the interview, or correct the one already on file. */
    public function store(Request $request, Resignation $resignation): RedirectResponse
    {
        abort_unless($this->scoped()->whereKey($resignation->id)->exists(), 404);

        $existing = ExitInterview::withoutGlobalScopes()->where('resignation_id', $resignation->id)->first();
        $this->authorize($existing ? 'update' : 'create', $existing ?? ExitInterview::class);
        $this->denyOwnRequest($resignation->employee);

        $data = $request->validate([
            'interview_date' => ['required', 'date'],
            'feedback' => ['nullable', 'string', 'max:2000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'would_rehire' => ['nullable', 'in:0,1'],
        ], [
            'rating.*' => 'The rating is a number from 1 to 5.',
            'would_rehire.in' => 'Choose yes or no for "would rehire", or leave it unanswered.',
        ]);
        $date = Carbon::parse($data['interview_date'])->startOfDay();

        $created = DB::transaction(function () use ($resignation, $data, $date) {
            $row = Resignation::withoutGlobalScopes()->whereKey($resignation->id)->lockForUpdate()->first();

            if (! in_array($row->status, self::LEAVING, true)) {
                throw ValidationException::withMessages(['interview_date' => 'This resignation is '.$row->status.', so there is no exit to interview for.']);
            }
            if ($date->lt($row->resignation_date)) {
                throw ValidationException::withMessages(['interview_date' => 'The interview can\'t be before the resignation date ('.$row->resignation_date->format('M j, Y').').']);
            }
            // This page records interviews that have happened.
            if ($date->gt(today())) {
                throw ValidationException::withMessages(['interview_date' => 'The interview date can\'t be in the future. Record it once it has taken place.']);
            }

            $values = [
                'interview_date' => $date->toDateString(),
                'feedback' => filled($data['feedback'] ?? null) ? trim($data['feedback']) : null,
                'rating' => $data['rating'] ?? null,
                'would_rehire' => isset($data['would_rehire']) ? (bool) $data['would_rehire'] : null,
            ];
            $interview = ExitInterview::withoutGlobalScopes()->where('resignation_id', $row->id)->lockForUpdate()->first();
            if ($interview) {
                // A correction keeps the name of whoever held the interview.
                $interview->forceFill($values)->save();

                return false;
            }
            (new ExitInterview)->forceFill($values + [
                'company_id' => $row->company_id, 'resignation_id' => $row->id, 'employee_id' => $row->employee_id, 'conducted_by_user_id' => Auth::id(),
            ])->save();

            return true;
        });
        $who = $resignation->employee?->name ?? 'the employee';

        return back()->with('status', $created ? 'Exit interview with '.$who.' recorded.' : 'Exit interview with '.$who.' updated.');
    }

    public function destroy(Resignation $resignation): RedirectResponse
    {
        abort_unless($this->scoped()->whereKey($resignation->id)->exists(), 404);

        $interview = ExitInterview::withoutGlobalScopes()->where('resignation_id', $resignation->id)->first();
        abort_unless($interview, 404);
        $this->authorize('delete', $interview);
        $this->denyOwnRequest($resignation->employee);

        $interview->delete();

        return back()->with('status', 'Exit interview with '.($resignation->employee?->name ?? 'the employee').' removed. It can be recorded again.');
    }

    /** Resignations this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return Resignation::query()
            ->where('resignations.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }
}
