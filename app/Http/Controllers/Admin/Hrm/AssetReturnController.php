<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\Resignation;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetReturnController extends Controller
{
    public const CONDITIONS = ['good' => 'Good', 'damaged' => 'Damaged', 'lost' => 'Lost'];

    protected const TABS = ['due', 'returned'];

    /** Employees who have left: whatever they still hold is overdue. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeAsset::class);

        // Anything that isn't a plain value (e.g. ?category[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'category' => array_key_exists($text('category'), EmployeeAsset::CATEGORIES) ? $text('category') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $people = $this->employeeQuery()->orderBy('name')->get(['id', 'name', 'department_id', 'status']);
        $left = $people->whereIn('status', self::INACTIVE)->pluck('id');
        // People whose resignation was accepted: their things are due by their last working day.
        $leaving = Resignation::query()->where('company_id', Company::current()->id)->where('status', 'accepted')
            ->whereIn('employee_id', $people->pluck('id'))->whereNotIn('employee_id', $left)
            ->orderBy('last_working_day')->get(['employee_id', 'last_working_day'])->unique('employee_id')->keyBy('employee_id');
        $dueIds = $left->merge($leaving->keys())->unique()->values();

        $filtered = fn () => $this->owned()
            ->when($state['category'], fn ($q, $category) => $q->where('category', $category))
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('asset_name', 'like', $like)->orWhere('asset_tag', 'like', $like)->orWhere('identifier', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'due' => $q->where('status', 'assigned')->whereIn('employee_id', $dueIds),
            'returned' => $q->where('status', 'returned'),
            default => $q->where('status', 'assigned'),
        };

        $assets = $tabbed($filtered(), $state['tab'])
            ->with('employee.department:id,name')
            // Waiting longest first; for returns, the most recent first.
            ->when($state['tab'] === 'returned', fn ($q) => $q->orderByDesc('returned_date')->orderByDesc('id'), fn ($q) => $q->orderBy('assigned_date')->orderBy('id'))
            ->paginate(25)
            ->withQueryString();

        $out = $this->owned()->where('status', 'assigned')->get(['id', 'employee_id']);
        $back = $this->owned()->where('status', 'returned')->get(['id', 'condition_at_return', 'returned_date']);
        $due = $out->whereIn('employee_id', $dueIds);
        // With the list narrowed to one person, everything they hold can be taken back in one go.
        $focus = $state['employee_id'] && $state['tab'] !== 'returned' ? $people->firstWhere('id', $state['employee_id']) : null;

        return view('admin.hrm.assets.returns', [
            'assets' => $assets,
            'state' => $state,
            'employees' => $people->whereIn('id', $this->owned()->distinct()->pluck('employee_id'))->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'leftIds' => $left->flip(),
            'leaving' => $leaving,
            'counts' => ['pending' => $tabbed($filtered(), '')->count(), 'due' => $tabbed($filtered(), 'due')->count(), 'returned' => $tabbed($filtered(), 'returned')->count()],
            'kpis' => [
                'out' => $out->count(),
                'holders' => $out->pluck('employee_id')->unique()->count(),
                'due' => $due->count(),
                'due_people' => $due->pluck('employee_id')->unique()->count(),
                'month' => $back->filter(fn (EmployeeAsset $a) => $a->returned_date && $a->returned_date->isSameMonth(today()))->count(),
                'returned' => $back->count(),
                'damaged' => $back->where('condition_at_return', 'damaged')->count(),
                'lost' => $back->where('condition_at_return', 'lost')->count(),
            ],
            'focus' => $focus ? ['employee' => $focus, 'count' => $out->where('employee_id', $focus->id)->count()] : null,
        ]);
    }

    public function update(Request $request, EmployeeAsset $asset): RedirectResponse
    {
        $this->ensureOwn($asset);
        $this->authorize('update', $asset);

        $data = $this->validated($request);
        $this->guardDate($asset, $data['returned_date']);

        $this->takeBack(collect([$asset->id]), $data);

        return back()->with('status', $asset->asset_name.' from '.($asset->employee?->name ?? 'the employee').' marked as '.($data['condition_at_return'] === 'lost' ? 'lost' : 'returned'.($data['condition_at_return'] === 'damaged' ? ' damaged' : '')).'.');
    }

    /** Take back several assets at once, e.g. everything a leaver holds. */
    public function bulk(Request $request): RedirectResponse
    {
        $this->authorize('update', new EmployeeAsset);

        $request->validate(['asset_ids' => ['required', 'array', 'min:1', 'max:200'], 'asset_ids.*' => ['integer', 'distinct']], ['asset_ids.required' => 'Pick at least one asset.']);
        $data = $this->validated($request);

        $assets = $this->owned()->where('status', 'assigned')->whereIn('id', $request->input('asset_ids'))->get();

        if ($assets->count() !== count($request->input('asset_ids'))) {
            throw ValidationException::withMessages(['asset_ids' => 'One or more of those assets can\'t be returned (not found, or already returned). Reload the page.']);
        }

        $assets->each(fn (EmployeeAsset $asset) => $this->guardDate($asset, $data['returned_date']));
        $this->takeBack($assets->pluck('id'), $data);

        return back()->with('status', $assets->count().' '.($assets->count() === 1 ? 'asset' : 'assets').' marked as returned.');
    }

    /** A return recorded by mistake: the asset goes back to being with the person. */
    public function undo(EmployeeAsset $asset): RedirectResponse
    {
        $this->ensureOwn($asset);
        $this->authorize('update', $asset);

        if ($asset->status !== 'returned') {
            return back()->withErrors(['asset' => $asset->asset_name.' is not marked as returned.']);
        }
        // A transfer closes the old holder's record; reopening it would put one item with two people.
        if (str_starts_with((string) $asset->remarks, 'Transferred to ')) {
            return back()->withErrors(['asset' => $asset->asset_name.' was transferred to someone else, so this return can\'t be undone. Transfer it back instead.']);
        }

        $clash = EmployeeAsset::withoutGlobalScopes()->where('company_id', $asset->company_id)->where('status', 'assigned')->whereKeyNot($asset->id)
            ->where(fn ($w) => $w
                ->when(filled($asset->asset_tag), fn ($q) => $q->orWhereRaw('LOWER(asset_tag) = ?', [mb_strtolower($asset->asset_tag)]))
                ->when(filled($asset->identifier) && $asset->category !== 'uniform', fn ($q) => $q->orWhere(fn ($x) => $x->where('category', $asset->category)->whereRaw('LOWER(identifier) = ?', [mb_strtolower($asset->identifier)])))
                // Nothing to compare by: never match.
                ->when(blank($asset->asset_tag) && (blank($asset->identifier) || $asset->category === 'uniform'), fn ($q) => $q->whereRaw('1 = 0')))
            ->with('employee:id,name')->first();

        if ($clash) {
            return back()->withErrors(['asset' => $asset->asset_name.' has since been given to '.($clash->employee?->name ?? 'someone else').', so this return can\'t be undone.']);
        }

        $asset->update(['status' => 'assigned', 'returned_date' => null, 'condition_at_return' => null, 'remarks' => null]);

        return back()->with('status', 'Return undone. '.$asset->asset_name.' is with '.($asset->employee?->name ?? 'the employee').' again.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'condition_at_return' => ['required', Rule::in(array_keys(self::CONDITIONS))],
            'returned_date' => ['nullable', 'date', 'before_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], ['returned_date.before_or_equal' => 'The return date can\'t be in the future.']);

        $remarks = filled($data['remarks'] ?? null) ? trim($data['remarks']) : null;

        if ($data['condition_at_return'] !== 'good' && $remarks === null) {
            throw ValidationException::withMessages(['remarks' => 'Say what happened when an asset is '.$data['condition_at_return'].'. The note is kept in the asset history.']);
        }

        return [
            'condition_at_return' => $data['condition_at_return'],
            // The old form had no date; it meant "today".
            'returned_date' => filled($data['returned_date'] ?? null) ? Carbon::parse($data['returned_date'])->toDateString() : today()->toDateString(),
            'remarks' => $remarks,
        ];
    }

    protected function guardDate(EmployeeAsset $asset, string $date): void
    {
        if ($asset->assigned_date && Carbon::parse($date)->lt($asset->assigned_date)) {
            throw ValidationException::withMessages(['returned_date' => $asset->asset_name.' was handed over on '.$asset->assigned_date->format('M j, Y').', so it can\'t have come back before that.']);
        }
    }

    /** Mark assets returned, refusing any that someone else returned or transferred a moment ago. */
    protected function takeBack($ids, array $data): void
    {
        DB::transaction(function () use ($ids, $data) {
            $rows = EmployeeAsset::withoutGlobalScopes()->whereIn('id', $ids)->lockForUpdate()->get();

            if ($rows->contains(fn (EmployeeAsset $row) => $row->status !== 'assigned')) {
                throw ValidationException::withMessages(['asset' => 'That asset has already been returned. Reload the page.']);
            }

            $rows->each(fn (EmployeeAsset $row) => $row->update($data + ['status' => 'returned']));
        });
    }

    /** 404 (not 403) for another company's or branch's asset, so the URL doesn't confirm it exists. */
    protected function ensureOwn(EmployeeAsset $asset): void
    {
        abort_unless($this->owned()->whereKey($asset->id)->exists(), 404);
    }

    protected function owned(): Builder
    {
        return EmployeeAsset::query()
            ->where('employee_assets.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
