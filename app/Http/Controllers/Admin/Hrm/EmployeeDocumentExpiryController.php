<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeDocument;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentExpiryController extends Controller
{
    use ExportsCsv;

    protected const TABS = ['expired', 'soon', 'valid', 'replaced'];

    /** The same "expiring soon" window as the document pages themselves. */
    public const SOON_DAYS = EmployeeDocumentCategoryController::SOON_DAYS;

    /** Employees who have left: their expired papers are nobody's job to renew. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** Kinds a person has one of at a time: a newer one replaces the older, whatever it is called. */
    protected const ONE_AT_A_TIME = ['passport', 'visa', 'contract', 'pan', 'aadhar', 'offer_letter', 'appointment_letter'];

    protected const PER_PAGE = 25;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $state = $this->state($request);
        $all = $this->rows();
        // People who have left are kept out unless asked for.
        $current = $all->where('left', false)->values();
        $base = $this->filtered($state['left'] ? $all : $current, $state);
        // "All" means everything still in force or needing attention; replaced copies only show on their own tab.
        $listed = match ($state['tab']) {
            '' => $base->where('status', '!=', 'replaced'),
            default => $base->where('status', $state['tab']),
        };
        $listed = $listed->values();
        $page = max(1, (int) (is_scalar($request->query('page')) ? $request->query('page') : 1));
        $expired = $current->where('status', 'expired');
        $soon = $current->where('status', 'soon');

        return view('admin.hrm.documents.expiry', [
            'documents' => (new LengthAwarePaginator($listed->forPage($page, self::PER_PAGE)->values(), $listed->count(), self::PER_PAGE, $page, ['path' => $request->url()]))->withQueryString(),
            'state' => $state,
            'employees' => $all->mapWithKeys(fn ($r) => [$r['document']->employee_id => $r['name']])->sort(),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $all->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'types' => $all->pluck('document.category')->filter()->unique()->mapWithKeys(fn ($type) => [$type => EmployeeDocument::TYPES[$type] ?? ucfirst($type)])->sort(),
            'counts' => ['all' => $base->where('status', '!=', 'replaced')->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $base->where('status', $tab)->count()])->all(),
            'kpis' => [
                'expired' => $expired->count(),
                'expired_people' => $expired->pluck('document.employee_id')->unique()->count(),
                'soon' => $soon->count(),
                'month' => $soon->where('days', '<=', 30)->count(),
                'next' => $current->whereIn('status', ['soon', 'valid'])->min('document.expiry_date'),
                'valid' => $current->where('status', 'valid')->count(),
                'tracked' => $current->where('status', '!=', 'replaced')->count(),
                'left' => $all->where('left', true)->whereIn('status', ['expired', 'soon'])->count(),
            ],
            'soonDays' => self::SOON_DAYS,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $state = $this->state($request);
        $all = $this->rows();
        $base = $this->filtered($state['left'] ? $all : $all->where('left', false)->values(), $state);
        $rows = $state['tab'] === '' ? $base->where('status', '!=', 'replaced') : $base->where('status', $state['tab']);
        // A cell starting with = + - @ would run as a formula when the file is opened in a spreadsheet.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;

        return $this->streamCsv(
            'document-expiry-'.today()->toDateString().'.csv',
            // The first five columns are where they always were; new ones are added at the end.
            ['Employee', 'Type', 'Document Name', 'Expiry Date', 'Status', 'Days Left', 'Employee Code', 'Department'],
            (function () use ($rows, $safe) {
                foreach ($rows as $row) {
                    $document = $row['document'];
                    yield array_map($safe, [
                        $row['name'],
                        EmployeeDocument::TYPES[$document->category] ?? $document->category,
                        $document->name,
                        $document->expiry_date->format('Y-m-d'),
                        self::LABELS[$row['status']],
                        $row['days'],
                        $document->employee?->employee_code,
                        $document->employee?->department?->name,
                    ]);
                }
            })()
        );
    }

    protected const LABELS = ['expired' => 'Expired', 'soon' => 'Expiring Soon', 'valid' => 'Valid', 'replaced' => 'Replaced'];

    protected function state(Request $request): array
    {
        // Anything that isn't a plain value (e.g. ?category[]=x) is ignored rather than crashing the page or the export.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        // The old page filtered with ?expiry_status=expired|expiring_soon|valid; those links keep working.
        $legacy = ['expired' => 'expired', 'expiring_soon' => 'soon', 'valid' => 'valid'][$text('expiry_status')] ?? '';
        $tab = $text('tab') !== '' ? $text('tab') : $legacy;

        return [
            'tab' => in_array($tab, self::TABS, true) ? $tab : '',
            'category' => array_key_exists($text('category'), EmployeeDocument::TYPES) ? $text('category') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
            'left' => $text('left') === '1' ? '1' : '',
        ];
    }

    protected function filtered(Collection $rows, array $state): Collection
    {
        $needle = mb_strtolower($state['search']);

        return $rows
            ->when($state['category'], fn ($c, $category) => $c->where('document.category', $category))
            ->when($state['employee_id'], fn ($c, $employeeId) => $c->where('document.employee_id', $employeeId))
            ->when($state['department_id'], fn ($c, $departmentId) => $c->where('department_id', $departmentId))
            ->when($needle !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r['name'].' '.$r['document']->employee?->employee_code.' '.$r['document']->name), $needle)))
            ->values();
    }

    /** Every document that has an expiry date, soonest first, with where it stands today. */
    protected function rows(): Collection
    {
        $scoped = fn () => EmployeeDocument::query()
            ->where('employee_documents.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));

        $dated = $scoped()->whereNotNull('expiry_date')->with('employee.department:id,name')->orderBy('expiry_date')->orderBy('id')->get();
        // Copies without an expiry date can still replace an expired one (a renewed contract with no end date).
        $undated = $scoped()->whereNull('expiry_date')->whereIn('employee_id', $dated->pluck('employee_id')->unique())->get(['id', 'employee_id', 'category', 'name', 'created_at']);
        $siblings = $dated->concat($undated)->groupBy(fn (EmployeeDocument $d) => $d->employee_id.'|'.$d->category);

        return $dated->map(function (EmployeeDocument $document) use ($siblings) {
            $days = (int) today()->diffInDays($document->expiry_date, false);
            $sameName = fn (EmployeeDocument $other) => mb_strtolower(trim((string) $other->name)) === mb_strtolower(trim((string) $document->name));
            // A newer copy of the same thing: later expiry, or no expiry and uploaded afterwards.
            $replaced = $siblings->get($document->employee_id.'|'.$document->category, collect())->contains(fn (EmployeeDocument $other) => $other->id !== $document->id
                && (in_array($document->category, self::ONE_AT_A_TIME, true) || $sameName($other))
                && ($other->expiry_date ? $other->expiry_date->gt($document->expiry_date) : ($other->created_at && $document->created_at && $other->created_at->gt($document->created_at))));

            return [
                'document' => $document,
                'name' => $document->employee?->name ?? 'Removed employee',
                'department_id' => $document->employee?->department_id,
                'left' => in_array($document->employee?->status, self::INACTIVE, true),
                'days' => $days,
                'status' => match (true) {
                    // Only an expired or expiring copy is worth calling "replaced"; a valid one is simply valid.
                    $replaced && $days <= self::SOON_DAYS => 'replaced',
                    $days < 0 => 'expired',
                    $days <= self::SOON_DAYS => 'soon',
                    default => 'valid',
                },
            ];
        });
    }
}
