<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Contact;
use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmImportExportController extends Controller
{
    use ExportsCsv;

    public const ENTITIES = ['leads' => 'Leads', 'accounts' => 'Accounts', 'contacts' => 'Contacts', 'opportunities' => 'Opportunities'];

    public function index(): View
    {
        return view('admin.crm.import-export.index', ['entities' => self::ENTITIES]);
    }

    public function export(string $entity)
    {
        abort_unless(array_key_exists($entity, self::ENTITIES), 404);
        $companyId = Company::current()->id;

        [$columns, $rows] = match ($entity) {
            'leads' => [['Name', 'Email', 'Phone', 'Company', 'Status', 'Score'],
                Lead::where('company_id', $companyId)->get()->map(fn ($r) => [$r->name, $r->email, $r->phone, $r->company_name, $r->status, $r->score])],
            'accounts' => [['Name', 'Industry', 'Phone', 'Website'],
                Account::where('company_id', $companyId)->get()->map(fn ($r) => [$r->name, $r->industry, $r->phone, $r->website])],
            'contacts' => [['Name', 'Email', 'Phone', 'Job Title', 'Company'],
                Contact::where('company_id', $companyId)->get()->map(fn ($r) => [$r->name, $r->email, $r->phone, $r->job_title, $r->company_name])],
            default => [['Name', 'Amount', 'Status', 'Expected Close'],
                Opportunity::where('company_id', $companyId)->get()->map(fn ($r) => [$r->name, $r->amount, $r->status, $r->expected_close_date?->format('Y-m-d')])],
        };

        return $this->streamCsv($entity.'.csv', $columns, $rows);
    }

    public function importLeads(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $companyId = Company::current()->id;
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (empty($data['name'] ?? $data['Name'] ?? null)) {
                continue;
            }

            Lead::create([
                'company_id' => $companyId,
                'name' => $data['name'] ?? $data['Name'],
                'email' => $data['email'] ?? $data['Email'] ?? null,
                'phone' => $data['phone'] ?? $data['Phone'] ?? null,
                'company_name' => $data['company'] ?? $data['Company'] ?? null,
                'status' => 'new',
            ]);
            $imported++;
        }
        fclose($handle);

        return back()->with('status', "Imported {$imported} lead(s).");
    }
}
