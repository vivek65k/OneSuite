<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificationController extends Controller
{
    protected const TABS = ['valid', 'expiring', 'expired'];

    /** A certificate that runs out within this many days counts as "expiring soon". */
    public const SOON_DAYS = 60;

    /** No certificate is valid for longer than this, and none is recorded from further back. */
    protected const MAX_YEARS = 50;

    /** Employees who have left; nothing new is recorded for them. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Certification::class);
        $this->expireOverdue();

        $state = $this->state($request);
        $certifications = $this->tabbed($this->filtered($state), $state['tab'])
            ->with('employee.department:id,name')
            ->when($state['tab'] === 'expiring', fn ($q) => $q->orderBy('expiry_date'), fn ($q) => $q->orderByDesc('issue_date'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'name', 'issuing_body', 'issue_date', 'expiry_date', 'status']);
        $renewed = $this->renewedIds($all);
        $live = $all->where('status', 'active');
        $soon = $live->filter(fn (Certification $c) => $c->expiry_date && $c->expiry_date->lte(today()->addDays(self::SOON_DAYS)));
        $lapsed = $all->where('status', 'expired')->reject(fn (Certification $c) => $renewed->has($c->id));
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'branch_id', 'status']);
        $staff = $people->whereNotIn('status', self::INACTIVE);

        return view('admin.hrm.learning.certifications.index', [
            'certifications' => $certifications,
            'state' => $state,
            'renewed' => $renewed,
            'employees' => $people->pluck('name', 'id'),
            'formPeople' => $staff->values(),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'names' => $all->pluck('name')->map(fn ($n) => trim($n))->unique(fn ($n) => mb_strtolower($n))->sort()->values(),
            'bodies' => $all->pluck('issuing_body')->filter()->map(fn ($n) => trim($n))->unique(fn ($n) => mb_strtolower($n))->sort()->values(),
            'counts' => ['all' => $this->filtered($state)->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $this->tabbed($this->filtered($state), $tab)->count()])->all(),
            'kpis' => [
                'valid' => $live->count(),
                'soon' => $soon->count(),
                'next' => $soon->sortBy('expiry_date')->first(),
                'lapsed' => $lapsed->count(),
                'lapsed_people' => $lapsed->pluck('employee_id')->unique()->count(),
                'holders' => $live->pluck('employee_id')->intersect($staff->pluck('id'))->unique()->count(),
                'staff' => $staff->count(),
            ],
            'canCreate' => $request->user()->can('create', Certification::class),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Certification::class);
        $this->expireOverdue();

        $state = $this->state($request);
        $rows = $this->tabbed($this->filtered($state), $state['tab'])->with('employee.department:id,name')->orderByDesc('issue_date')->orderByDesc('id')->get();
        // A cell starting with = + - @ would run as a formula when the file is opened in a spreadsheet.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;

        return response()->streamDownload(function () use ($rows, $safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee', 'Employee code', 'Department', 'Certification', 'Issuing body', 'Credential ID', 'Issued', 'Expires', 'Status']);

            foreach ($rows as $c) {
                fputcsv($out, array_map($safe, [
                    $c->employee?->name, $c->employee?->employee_code, $c->employee?->department?->name, $c->name, $c->issuing_body, $c->credential_id,
                    $c->issue_date?->toDateString(), $c->expiry_date?->toDateString() ?? 'No expiry', $this->label($c),
                ]));
            }

            fclose($out);
        }, 'certifications-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Certification::class);

        $data = $this->validated($request);
        $employee = $this->employeeQuery()->whereNotIn('status', self::INACTIVE)->whereKey($request->input('employee_id'))->first(['id', 'name']);

        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Choose an employee from the list.']);
        }

        $this->guardDuplicate($employee->id, $data);

        Certification::create($data + ['company_id' => Company::current()->id, 'employee_id' => $employee->id, 'status' => $this->statusFor($data['expiry_date'])]);

        return redirect()->route('admin.certifications.index')->with('status', '"'.$data['name'].'" recorded for '.$employee->name.'.'.($this->statusFor($data['expiry_date']) === 'expired' ? ' It has already expired, so it is listed as expired.' : ''));
    }

    public function update(Request $request, Certification $certification): RedirectResponse
    {
        $this->ensureOwn($certification);
        $this->authorize('update', $certification);

        $passed = $certification->expiry_date && $certification->expiry_date->lt(today());

        // The row buttons send only a status; the edit dialog sends the details.
        if ($request->has('status') && ! $request->has('name')) {
            $data = $request->validate(['status' => ['required', Rule::in(array_keys(Certification::STATUSES))]]);

            if ($data['status'] === 'active' && $passed) {
                return back()->withErrors(['status' => '"'.$certification->name.'" ran out on '.$certification->expiry_date->format('M j, Y').'. Renew it with a new expiry date instead of marking it active.']);
            }

            $certification->update($data);

            return back()->with('status', $data['status'] === 'active' ? '"'.$certification->name.'" is valid again.' : '"'.$certification->name.'" marked as no longer valid.');
        }

        $data = $this->validated($request);
        $this->guardDuplicate($certification->employee_id, $data, $certification);

        // Withdrawn by hand (not by date): a typo fix must not quietly make it valid again.
        $withdrawn = $certification->status === 'expired' && ! $passed;
        $sameExpiry = ($certification->expiry_date?->toDateString()) === $data['expiry_date'];

        $certification->update($data + ['status' => $withdrawn && $sameExpiry ? 'expired' : $this->statusFor($data['expiry_date'])]);

        return back()->with('status', 'Certification updated.');
    }

    /** A renewal is a new certificate: the old one stays on record as expired. */
    public function renew(Request $request, Certification $certification): RedirectResponse
    {
        $this->ensureOwn($certification);
        $this->authorize('update', $certification);
        $this->authorize('create', Certification::class);

        $request->merge(['name' => $certification->name, 'issuing_body' => $certification->issuing_body, 'credential_id' => $request->input('credential_id', $certification->credential_id)]);
        $data = $this->validated($request);

        if (Carbon::parse($data['issue_date'])->lte($certification->issue_date)) {
            throw ValidationException::withMessages(['issue_date' => 'The renewal must be issued after the current certificate ('.$certification->issue_date->format('M j, Y').').']);
        }
        if ($this->statusFor($data['expiry_date']) === 'expired') {
            throw ValidationException::withMessages(['expiry_date' => 'A renewal can\'t already be expired. Enter the new expiry date.']);
        }

        DB::transaction(function () use ($certification, $data) {
            Certification::create($data + ['company_id' => $certification->company_id, 'employee_id' => $certification->employee_id, 'status' => 'active']);
            $certification->update(['status' => 'expired']);
        });

        return back()->with('status', '"'.$certification->name.'" renewed'.($data['expiry_date'] ? ' until '.Carbon::parse($data['expiry_date'])->format('M j, Y') : ' with no expiry').'. The earlier certificate is kept as expired.');
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $this->ensureOwn($certification);
        $this->authorize('delete', $certification);

        $certification->delete();

        return back()->with('status', 'Certification removed.');
    }

    protected function validated(Request $request): array
    {
        $oldest = today()->subYears(self::MAX_YEARS)->toDateString();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'issuing_body' => ['nullable', 'string', 'max:255'],
            'credential_id' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$oldest],
            'expiry_date' => ['nullable', 'date', 'after:issue_date'],
        ], [
            'issue_date.before_or_equal' => 'The issue date can\'t be in the future.',
            'issue_date.after_or_equal' => 'The issue date can be at most '.self::MAX_YEARS.' years back.',
            'expiry_date.after' => 'The expiry date must be after the issue date.',
        ]);

        $issued = Carbon::parse($data['issue_date'])->startOfDay();
        $expires = filled($data['expiry_date'] ?? null) ? Carbon::parse($data['expiry_date'])->startOfDay() : null;

        if ($expires && $expires->gt($issued->copy()->addYears(self::MAX_YEARS))) {
            throw ValidationException::withMessages(['expiry_date' => 'A certificate can be valid for at most '.self::MAX_YEARS.' years. Leave the expiry empty if it never runs out.']);
        }

        return [
            'name' => trim($data['name']),
            'issuing_body' => filled($data['issuing_body'] ?? null) ? trim($data['issuing_body']) : null,
            'credential_id' => filled($data['credential_id'] ?? null) ? trim($data['credential_id']) : null,
            'issue_date' => $issued->toDateString(),
            'expiry_date' => $expires?->toDateString(),
        ];
    }

    /** The same certificate entered twice, or one credential number given to two people. */
    protected function guardDuplicate(int $employeeId, array $data, ?Certification $except = null): void
    {
        $same = fn () => Certification::query()->where('company_id', Company::current()->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->when($except, fn ($q) => $q->whereKeyNot($except->id));

        if ($same()->where('employee_id', $employeeId)->whereDate('issue_date', $data['issue_date'])->exists()) {
            throw ValidationException::withMessages(['name' => 'This person already has "'.$data['name'].'" issued on '.Carbon::parse($data['issue_date'])->format('M j, Y').'.']);
        }

        if ($data['credential_id'] !== null && $same()->where('employee_id', '!=', $employeeId)->where('credential_id', $data['credential_id'])->exists()) {
            throw ValidationException::withMessages(['credential_id' => 'Credential ID "'.$data['credential_id'].'" is already recorded for someone else\'s "'.$data['name'].'". Check the number.']);
        }
    }

    protected function statusFor(?string $expiry): string
    {
        return $expiry !== null && Carbon::parse($expiry)->lt(today()) ? 'expired' : 'active';
    }

    protected function label(Certification $c): string
    {
        return match (true) {
            $c->status === 'expired' && $c->expiry_date && $c->expiry_date->lt(today()) => 'Expired',
            $c->status === 'expired' => 'No longer valid',
            $c->expiry_date && $c->expiry_date->lte(today()->addDays(self::SOON_DAYS)) => 'Expiring soon',
            default => 'Valid',
        };
    }

    /** Nothing used to flip a certificate to "expired" when its date passed; it stayed "active" for ever. */
    protected function expireOverdue(): void
    {
        Certification::withoutGlobalScopes()->where('company_id', Company::current()->id)->where('status', 'active')
            ->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today())->update(['status' => 'expired']);
    }

    /** Expired certificates that have a newer one of the same name for the same person. */
    protected function renewedIds(Collection $all): Collection
    {
        return $all->where('status', 'expired')->filter(fn (Certification $old) => $all->contains(fn (Certification $c) => $c->id !== $old->id
            && $c->employee_id === $old->employee_id && $c->status === 'active'
            && mb_strtolower(trim($c->name)) === mb_strtolower(trim($old->name)) && $c->issue_date->gte($old->issue_date)))->pluck('id')->flip();
    }

    protected function state(Request $request): array
    {
        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;

        return [
            // The old page filtered with ?status=active|expired; those links keep working.
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : ($text('status') === 'expired' ? 'expired' : ''),
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];
    }

    protected function filtered(array $state): Builder
    {
        return $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('issuing_body', 'like', $like)->orWhere('credential_id', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });
    }

    protected function tabbed(Builder $query, string $tab): Builder
    {
        $soon = today()->addDays(self::SOON_DAYS);

        return match ($tab) {
            'valid' => $query->where('status', 'active')->where(fn ($w) => $w->whereNull('expiry_date')->orWhereDate('expiry_date', '>', $soon)),
            'expiring' => $query->where('status', 'active')->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', $soon),
            'expired' => $query->where('status', 'expired'),
            default => $query,
        };
    }

    /** 404 (not 403) for another company's or branch's certificate, so the URL doesn't confirm it exists. */
    protected function ensureOwn(Certification $certification): void
    {
        abort_unless($this->scoped()->whereKey($certification->id)->exists(), 404);
    }

    protected function scoped(): Builder
    {
        return Certification::query()
            ->where('certifications.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
