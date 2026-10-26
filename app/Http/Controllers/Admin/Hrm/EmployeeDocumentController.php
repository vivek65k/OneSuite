<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Support\BranchContext;
use App\Support\DataScope;
use App\Support\ReportingChain;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    /** Employees who have left don't get new documents filed from these pages. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** An expiry date further out than this is almost certainly a typo. */
    protected const MAX_YEARS_AHEAD = 30;

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeDocument::class);

        $data = $request->validate([
            'employee_id' => ['required'],
            'category' => ['required', Rule::in(array_keys(EmployeeDocument::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'file' => EmployeeDocument::fileRules(),
            'expiry_date' => ['nullable', 'date'],
        ], EmployeeDocument::fileMessages());

        // The employee must be one this login may file for: its own company and, when tied to a branch, that branch.
        $employee = is_scalar($data['employee_id']) && ctype_digit((string) $data['employee_id'])
            ? $this->employees()->whereNotIn('status', self::INACTIVE)->whereKey((int) $data['employee_id'])->first(['id', 'name'])
            : null;

        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Choose an employee from the list.']);
        }

        $expiry = $this->expiry($data['expiry_date'] ?? null);

        EmployeeDocument::create([
            'company_id' => Company::current()->id,
            'employee_id' => $employee->id,
            'category' => $data['category'],
            'name' => trim($data['name']),
            'expiry_date' => $expiry,
            'file_path' => EmployeeDocument::storeUpload($request->file('file')),
        ]);

        return back()->with('status', EmployeeDocument::TYPES[$data['category']].' filed for '.$employee->name.'.'.($expiry && Carbon::parse($expiry)->lt(today()) ? ' Note that it has already expired.' : ''));
    }

    /** Correct a document's name or expiry date, or swap in a newer copy of the file. */
    public function update(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->ensureOwn($document);
        $this->authorize('update', $document);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'file' => EmployeeDocument::fileRules(required: false),
        ], EmployeeDocument::fileMessages());

        $values = ['name' => trim($data['name']), 'expiry_date' => $this->expiry($data['expiry_date'] ?? null)];

        if ($request->hasFile('file')) {
            $document->deleteFile();
            $values['file_path'] = EmployeeDocument::storeUpload($request->file('file'));
        }

        $document->update($values);

        return back()->with('status', 'Document updated.'.($request->hasFile('file') ? ' The file was replaced.' : ''));
    }

    public function destroy(EmployeeDocument $document): RedirectResponse
    {
        $this->ensureOwn($document);
        $this->authorize('delete', $document);

        // The model removes the stored file along with the record.
        $document->delete();

        return back()->with('status', 'Document removed.');
    }

    /**
     * Open a document. This is the only way to a file: the person it belongs
     * to, anyone above them in the reporting line, and HR logins whose
     * documents permission covers that employee.
     */
    public function file(Request $request, int $document): StreamedResponse
    {
        // Loaded without the screen scope: who may see it is decided right here.
        $record = EmployeeDocument::withoutGlobalScopes()->where('company_id', Company::current()->id)->whereKey($document)->first();

        abort_unless($record && $this->mayOpen($request, $record), 404);

        $disk = $record->disk();

        abort_unless($disk, 404, 'The file for this document is missing.');

        $name = Str::slug($record->name ?: 'document').'.'.($record->extension ?: 'bin');
        // PDFs and images open in the browser; Word files download. Nothing is ever served as a web page or script.
        $inline = in_array($record->extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true);

        return Storage::disk($disk)->response($record->file_path, $name, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'], $inline ? 'inline' : 'attachment');
    }

    protected function mayOpen(Request $request, EmployeeDocument $record): bool
    {
        $user = $request->user();
        $mine = DataScope::employeeIdOf($user);

        // Their own document (My Documents).
        if ($mine !== null && (int) $record->employee_id === $mine) {
            return true;
        }

        // Someone who reports to them, directly or further down (Team Documents).
        if ($mine !== null && in_array((int) $record->employee_id, ReportingChain::below($mine, (int) $record->company_id), true)) {
            return true;
        }

        // HR: the documents permission, for an employee its data access covers and in the branch it is working in.
        if (! $user->can('view_any_employee_document') || ! DataScope::covers($user, 'view_any_employee_document', (int) $record->employee_id)) {
            return false;
        }

        return ! BranchContext::isRestricted()
            || Employee::withoutGlobalScopes()->whereKey($record->employee_id)->where('branch_id', BranchContext::currentBranchId())->exists();
    }

    protected function expiry(?string $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        $expiry = Carbon::parse($date)->startOfDay();

        if ($expiry->gt(today()->addYears(self::MAX_YEARS_AHEAD)) || $expiry->lt(today()->subYears(self::MAX_YEARS_AHEAD))) {
            throw ValidationException::withMessages(['expiry_date' => 'Check the expiry date: it is more than '.self::MAX_YEARS_AHEAD.' years away.']);
        }

        return $expiry->toDateString();
    }

    /** 404 (not 403) for another company's or branch's document, so the URL doesn't confirm it exists. */
    protected function ensureOwn(EmployeeDocument $document): void
    {
        abort_unless(EmployeeDocument::query()
            ->where('employee_documents.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())))
            ->whereKey($document->id)->exists(), 404);
    }

    protected function employees(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
