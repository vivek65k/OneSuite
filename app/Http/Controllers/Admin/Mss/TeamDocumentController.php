<?php

namespace App\Http\Controllers\Admin\Mss;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TeamDocumentController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['expiring', 'personal', 'company'];

    /** People who have left drop off the page (their papers still show when picked by name). */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** Papers every employee's file should have; the same list the employee sees on My Documents. */
    public const EXPECTED = ['pan', 'aadhar', 'offer_letter', 'appointment_letter'];

    /** Papers that belong to the person rather than being issued by the company. */
    public const PERSONAL = ['pan', 'aadhar', 'passport', 'visa', 'certificate', 'other'];

    public const SOON_DAYS = 60;

    /** The list shows the most recent documents; narrowing by person or type brings up the rest. */
    protected const LIST_LIMIT = 300;

    public function index(Request $request): View
    {
        $manager = $this->currentEmployee();

        // Anything that isn't a plain value (e.g. ?type[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';

        // The team is the manager's own reporting line, read by id: it doesn't depend on what their role may browse.
        $everyone = Employee::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('id', $this->teamEmployeeIds())
            ->with('department:id,name')->orderBy('name')->get()
            ->each(fn (Employee $e) => $e->setAttribute('is_direct', (int) $e->manager_id === (int) $manager->id))->keyBy('id');
        // A filter for someone outside the team is simply ignored.
        $focus = ctype_digit($text('employee_id')) ? $everyone->get((int) $text('employee_id')) : null;
        $people = $focus ? collect([$focus->id => $focus]) : $everyone->reject(fn (Employee $e) => in_array($e->status, self::INACTIVE, true));
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'employee_id' => $focus?->id,
            'type' => array_key_exists($text('type'), EmployeeDocument::TYPES) ? $text('type') : '',
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $all = EmployeeDocument::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('employee_id', $people->keys())
            ->orderByDesc('created_at')->orderByDesc('id')->get()->map(function (EmployeeDocument $d) use ($people) {
                $days = $d->expiry_date ? (int) today()->diffInDays($d->expiry_date, false) : null;
                $d->setRelation('employee', $people[$d->employee_id]);
                $d->setAttribute('is_personal', blank($d->category) || in_array($d->category, self::PERSONAL, true));
                $d->setAttribute('days_left', $days);
                $d->setAttribute('expiry_state', $days === null ? 'none' : ($days < 0 ? 'expired' : ($days <= self::SOON_DAYS ? 'soon' : 'valid')));

                return $d;
            });

        $needle = mb_strtolower($state['search']);
        $filtered = $all
            ->when($state['type'] !== '', fn (Collection $c) => $c->where('category', $state['type']))
            ->when($needle !== '', fn (Collection $c) => $c->filter(fn (EmployeeDocument $d) => str_contains(mb_strtolower($d->name.' '.$d->employee->name.' '.$d->employee->employee_code), $needle)))
            ->values();
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            // The most urgent first.
            'expiring' => $rows->whereIn('expiry_state', ['expired', 'soon'])->sortBy('days_left'),
            'personal' => $rows->where('is_personal', true),
            'company' => $rows->where('is_personal', false),
            default => $rows,
        };
        $rows = $tabbed($filtered, $state['tab'])->values();

        // Per person: which of the expected papers are on file, and what is running out.
        $byPerson = $people->map(function (Employee $e) use ($all) {
            $mine = $all->where('employee_id', $e->id);
            $have = $mine->pluck('category')->filter()->unique();

            return [
                'employee' => $e,
                'count' => $mine->count(),
                'have' => collect(self::EXPECTED)->mapWithKeys(fn ($type) => [$type => $have->contains($type)]),
                'missing' => collect(self::EXPECTED)->reject(fn ($type) => $have->contains($type))->map(fn ($type) => EmployeeDocument::TYPES[$type])->values(),
                'expired' => $mine->where('expiry_state', 'expired')->count(),
                'soon' => $mine->where('expiry_state', 'soon')->count(),
            ];
        })->values();
        $next = $all->where('expiry_state', 'soon')->sortBy('days_left')->first();

        return view('admin.mss.documents', [
            'state' => $state,
            'hasTeam' => $everyone->isNotEmpty(),
            'everyone' => $everyone->values(),
            'focus' => $focus,
            'documents' => $rows->take(self::LIST_LIMIT),
            'shownOf' => [min($rows->count(), self::LIST_LIMIT), $rows->count()],
            'counts' => ['all' => $filtered->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($filtered, $tab)->count()])->all(),
            'typeCounts' => $all->countBy(fn (EmployeeDocument $d) => (string) $d->category),
            'byPerson' => $byPerson,
            'kpis' => [
                'documents' => $all->count(),
                'people' => $people->count(),
                'with' => $byPerson->where('count', '>', 0)->count(),
                'expired' => $all->where('expiry_state', 'expired')->count(),
                'expired_people' => $all->where('expiry_state', 'expired')->pluck('employee_id')->unique()->count(),
                'soon' => $all->where('expiry_state', 'soon')->count(),
                'next' => $next,
                'incomplete' => $byPerson->filter(fn ($p) => $p['missing']->isNotEmpty())->count(),
                'missing_papers' => (int) $byPerson->sum(fn ($p) => $p['missing']->count()),
            ],
        ]);
    }
}
