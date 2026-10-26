<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MyDocumentController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['mine', 'company', 'expiring'];

    /**
     * Papers that belong to the person: they upload, correct and remove these themselves.
     * Everything else (offer letter, contract, relieving letter, salary slip...) is issued
     * by the company; an employee can open those but not add, change or delete them.
     */
    public const PERSONAL = ['pan', 'aadhar', 'passport', 'visa', 'certificate', 'other'];

    /** Of the personal papers, the ones that run out. */
    public const EXPIRING = ['passport', 'visa', 'certificate', 'other'];

    /** What a complete file looks like, and who provides each paper. */
    public const EXPECTED = ['pan' => 'you', 'aadhar' => 'you', 'offer_letter' => 'HR', 'appointment_letter' => 'HR'];

    public const SOON_DAYS = 60;

    /** An expiry date further out than this is almost certainly a typo. */
    protected const MAX_YEARS_AHEAD = 30;

    /** Enough for anyone's papers; stops the page being used as free file storage. */
    protected const MAX_DOCUMENTS = 40;

    public function index(Request $request): View
    {
        $employee = $this->currentEmployee();

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = ['tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : ''];

        $all = $this->mine($employee)->orderByDesc('created_at')->orderByDesc('id')->get()->map(function (EmployeeDocument $d) {
            $days = $d->expiry_date ? (int) today()->diffInDays($d->expiry_date, false) : null;
            $d->setAttribute('is_personal', $this->isPersonal($d));
            $d->setAttribute('days_left', $days);
            $d->setAttribute('expiry_state', $days === null ? 'none' : ($days < 0 ? 'expired' : ($days <= self::SOON_DAYS ? 'soon' : 'valid')));

            return $d;
        });
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'mine' => $rows->where('is_personal', true),
            'company' => $rows->where('is_personal', false),
            'expiring' => $rows->whereIn('expiry_state', ['expired', 'soon'])->sortBy('days_left'),
            default => $rows,
        };
        $have = $all->pluck('category')->filter()->unique();
        $personalCount = $all->where('is_personal', true)->count();

        return view('admin.ess.documents', [
            'state' => $state,
            'documents' => $tabbed($all, $state['tab'])->values(),
            'counts' => ['all' => $all->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($all, $tab)->count()])->all(),
            'kpis' => [
                'total' => $all->count(),
                'personal' => $personalCount,
                'company' => $all->count() - $personalCount,
                'expired' => $all->where('expiry_state', 'expired')->count(),
                'soon' => $all->where('expiry_state', 'soon')->count(),
                'next' => $all->where('expiry_state', 'soon')->sortBy('days_left')->first(),
            ],
            'checklist' => collect(self::EXPECTED)->map(fn ($from, $type) => ['label' => EmployeeDocument::TYPES[$type], 'from' => $from, 'done' => $have->contains($type)]),
            'types' => collect(EmployeeDocument::TYPES)->only(self::PERSONAL),
            'canUpload' => $personalCount < self::MAX_DOCUMENTS,
            'maxDocuments' => self::MAX_DOCUMENTS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        $data = $request->validate([
            'category' => ['required', Rule::in(self::PERSONAL)],
            'name' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'file' => EmployeeDocument::fileRules(),
        ], EmployeeDocument::fileMessages() + [
            'category.required' => 'Choose what kind of document this is.',
            'category.in' => 'That kind of document is issued by the company. Ask HR to add it to your file.',
            'file.required' => 'Choose the file to upload.',
            'file.uploaded' => 'The file could not be uploaded. It may be larger than the server allows.',
        ]);

        if ($this->mine($employee)->whereIn('category', self::PERSONAL)->count() >= self::MAX_DOCUMENTS) {
            throw ValidationException::withMessages(['file' => 'You already have '.self::MAX_DOCUMENTS.' documents. Remove one you no longer need first.']);
        }

        $label = EmployeeDocument::TYPES[$data['category']];
        $expiry = $this->expiry($data['expiry_date'] ?? null, $data['category']);

        (new EmployeeDocument)->forceFill([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'category' => $data['category'],
            // A blank name falls back to the kind of document: "PAN", "Passport".
            'name' => filled($data['name'] ?? null) ? trim(strip_tags($data['name'])) : $label,
            'expiry_date' => $expiry,
            // Private storage: the file is only reachable through the checked download link.
            'file_path' => EmployeeDocument::storeUpload($request->file('file')),
        ])->save();

        return back()->with('status', $label.' uploaded.'.($expiry && Carbon::parse($expiry)->lt(today()) ? ' Note that it has already expired.' : ''));
    }

    /** Rename one of your own papers, correct its expiry date, or swap in a newer copy. */
    public function update(Request $request, int $document): RedirectResponse
    {
        $record = $this->owned($document);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'file' => EmployeeDocument::fileRules(required: false),
        ], EmployeeDocument::fileMessages() + ['file.uploaded' => 'The file could not be uploaded. It may be larger than the server allows.']);

        $values = ['name' => trim(strip_tags($data['name'])) ?: $record->name, 'expiry_date' => $this->expiry($data['expiry_date'] ?? null, (string) $record->category)];

        if ($request->hasFile('file')) {
            // The new copy is saved before the old one is removed, so a failed upload can't lose the document.
            $old = $record->replicate(['id']);
            $values['file_path'] = EmployeeDocument::storeUpload($request->file('file'));
            $old->deleteFile();
        }
        $record->forceFill($values)->save();

        return back()->with('status', $record->name.' updated.'.($request->hasFile('file') ? ' The file was replaced.' : ''));
    }

    public function destroy(int $document): RedirectResponse
    {
        $record = $this->owned($document);

        // The model removes the stored file along with the record.
        $record->delete();

        return back()->with('status', $record->name.' removed.');
    }

    /** This employee's own documents, whatever their role may browse elsewhere. */
    protected function mine(Employee $employee): Builder
    {
        return EmployeeDocument::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id);
    }

    /** One of the employee's own papers that they may change: 404 for anyone else's, refused for a company-issued one. */
    protected function owned(int $id): EmployeeDocument
    {
        $record = $this->mine($this->currentEmployee())->whereKey($id)->firstOrFail();

        if (! $this->isPersonal($record)) {
            throw ValidationException::withMessages(['document' => ($record->name ?: 'This document').' was issued by the company, so only HR can change or remove it.']);
        }

        return $record;
    }

    /** Uncategorized uploads came from this page before it asked for a type, so they count as the person's own. */
    protected function isPersonal(EmployeeDocument $document): bool
    {
        return blank($document->category) || in_array($document->category, self::PERSONAL, true);
    }

    protected function expiry(?string $date, string $category): ?string
    {
        // A PAN or Aadhar never runs out; a date sent for one is dropped rather than stored.
        if (blank($date) || ! in_array($category, self::EXPIRING, true)) {
            return null;
        }

        $expiry = Carbon::parse($date)->startOfDay();

        if ($expiry->gt(today()->addYears(self::MAX_YEARS_AHEAD)) || $expiry->lt(today()->subYears(self::MAX_YEARS_AHEAD))) {
            throw ValidationException::withMessages(['expiry_date' => 'Check the expiry date: it is more than '.self::MAX_YEARS_AHEAD.' years away.']);
        }

        return $expiry->toDateString();
    }
}
