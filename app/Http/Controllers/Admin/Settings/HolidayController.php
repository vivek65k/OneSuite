<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Holiday;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HolidayController extends Controller
{
    use ExportsCsv;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Holiday::class);

        $year = $this->year($request->query('year'));
        $state = [
            'year' => $year,
            'search' => is_string($request->query('search')) ? trim($request->query('search')) : '',
            'type' => in_array($request->query('type'), ['yearly', 'once'], true) ? $request->query('type') : '',
            'view' => $request->query('view') === 'calendar' ? 'calendar' : 'list',
        ];

        $all = $this->scoped()->orderBy('date')->get();
        $inYear = $this->occurrences($all, $year);

        $shown = $inYear
            ->when($state['type'] === 'yearly', fn ($c) => $c->filter(fn ($o) => $o['holiday']->is_recurring))
            ->when($state['type'] === 'once', fn ($c) => $c->reject(fn ($o) => $o['holiday']->is_recurring))
            ->when($state['search'] !== '', fn ($c) => $c->filter(fn ($o) => str_contains(mb_strtolower($o['holiday']->name), mb_strtolower($state['search']))))
            ->values();

        // Next holiday from today, looking into next year when this year is done.
        $next = $this->occurrences($all, now()->year)->merge($this->occurrences($all, now()->year + 1))
            ->first(fn ($o) => $o['date']->gte(today()));

        return view('admin.settings.organization.holidays.index', [
            'occurrences' => $shown,
            'state' => $state,
            'counts' => [
                'all' => $inYear->count(),
                'yearly' => $inYear->filter(fn ($o) => $o['holiday']->is_recurring)->count(),
                'once' => $inYear->reject(fn ($o) => $o['holiday']->is_recurring)->count(),
            ],
            'kpis' => [
                'total' => $inYear->count(),
                'weekdays' => $inYear->reject(fn ($o) => $o['date']->isWeekend())->count(),
                'remaining' => $inYear->filter(fn ($o) => $o['date']->gte(today()))->count(),
                'long_weekends' => $inYear->filter(fn ($o) => $o['long_weekend'])->count(),
                'next' => $next,
            ],
            'years' => $all->map(fn ($h) => $h->date->year)->push(now()->year)->unique()->sort()->values(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Holiday::class);

        $year = $request->filled('year') ? $this->year($request->query('year')) : null;
        $rows = $year
            ? $this->occurrences($this->scoped()->get(), $year)
            : $this->scoped()->orderBy('date')->get()->map(fn ($h) => ['holiday' => $h, 'date' => $h->date]);

        return $this->streamCsv(
            'holidays-'.($year ?? 'all').'-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Date', 'Weekday', 'Recurring yearly'],
            (function () use ($rows) {
                foreach ($rows as $row) {
                    yield [$row['holiday']->name, $row['date']->format('Y-m-d'), $row['date']->format('l'), $row['holiday']->is_recurring ? 'Yes' : 'No'];
                }
            })()
        );
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Holiday::class);

        $holiday = new Holiday();
        if ($date = $this->validDate($request->query('date'))) {
            $holiday->date = $date;
        }

        return view('admin.settings.organization.holidays.create', ['holiday' => $holiday, 'existing' => $this->existingPayload()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Holiday::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        $holiday = Holiday::create($data);

        return redirect()->route('admin.holidays.index', ['year' => $holiday->date->year])->with('status', "“{$holiday->name}” added for {$holiday->date->format('l, M j, Y')}.");
    }

    public function edit(Holiday $holiday): View
    {
        $this->authorize('update', $holiday);
        $this->ensureOwn($holiday);

        return view('admin.settings.organization.holidays.edit', ['holiday' => $holiday, 'existing' => $this->existingPayload($holiday->id)]);
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $this->authorize('update', $holiday);
        $this->ensureOwn($holiday);

        $holiday->update($this->validated($request, $holiday));

        return redirect()->route('admin.holidays.index', ['year' => $holiday->date->year])->with('status', "“{$holiday->name}” updated.");
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->authorize('delete', $holiday);
        $this->ensureOwn($holiday);

        $holiday->delete();

        return back(fallback: route('admin.holidays.index'))->with('status', "“{$holiday->name}” deleted.");
    }

    /**
     * Each holiday's date within $year: recurring ones repeat on the same
     * month/day every year, one-time ones only count in their own year.
     *
     * @return Collection<int, array{holiday: Holiday, date: Carbon, long_weekend: bool}>
     */
    protected function occurrences(Collection $holidays, int $year): Collection
    {
        return $holidays
            ->map(function ($holiday) use ($year) {
                if (! $holiday->is_recurring) {
                    $date = $holiday->date->copy();
                } elseif ($holiday->date->year > $year) {
                    return null; // A yearly holiday starts in the year it was added.
                } else {
                    // Feb 29 falls back to Feb 28 in non-leap years.
                    $date = Carbon::create($year, $holiday->date->month, min($holiday->date->day, Carbon::create($year, $holiday->date->month, 1)->daysInMonth));
                }

                return $date->year === $year ? ['holiday' => $holiday, 'date' => $date->startOfDay(), 'long_weekend' => in_array($date->dayOfWeekIso, [1, 5], true)] : null;
            })
            ->filter()
            ->sortBy(fn ($o) => $o['date']->timestamp)
            ->values();
    }

    protected function validated(Request $request, ?Holiday $ignore = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
        ]);

        $data['is_recurring'] = $request->boolean('is_recurring');
        $date = Carbon::parse($data['date']);

        // No two holidays on the same day (a yearly one clashes with any year).
        $clash = $this->scoped()
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))
            ->get()
            ->first(fn ($h) => $h->date->format('m-d') === $date->format('m-d')
                && ($h->date->year === $date->year || ($h->is_recurring && $h->date->year <= $date->year) || ($data['is_recurring'] && $date->year <= $h->date->year)));
        if ($clash) {
            throw ValidationException::withMessages(['date' => "“{$clash->name}” is already on {$date->format('M j')}".($clash->is_recurring ? ' every year' : '').'.']);
        }

        return $data;
    }

    protected function ensureOwn(Holiday $holiday): void
    {
        abort_unless((int) $holiday->company_id === (int) Company::current()->id, 404);
    }

    protected function scoped(): Builder
    {
        return Holiday::query()->where('company_id', Company::current()->id);
    }

    /** Existing holidays for the form's clash warning: [{name, date, yearly}]. */
    protected function existingPayload(?int $ignoreId = null): array
    {
        return $this->scoped()->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->get()
            ->map(fn ($h) => ['name' => $h->name, 'date' => $h->date->toDateString(), 'yearly' => (bool) $h->is_recurring])
            ->all();
    }

    protected function year(mixed $value): int
    {
        $year = is_numeric($value) ? (int) $value : now()->year;

        return $year >= 2000 && $year <= 2100 ? $year : now()->year;
    }

    protected function validDate(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }
}
