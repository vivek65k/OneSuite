<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Contact;
use App\Models\Crm\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadConversionController extends Controller
{
    protected const SORTABLE = ['name', 'score', 'created_at'];

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'score';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $base = Lead::query()->where('company_id', $companyId)->where('status', 'qualified');

        if ($search !== '') {
            $base->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"));
        }

        $leads = (clone $base)->with(['source', 'assignedTo'])->orderBy($sort, $direction)->paginate(25)->withQueryString();

        return view('admin.crm.conversion.index', [
            'leads' => $leads,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'qualifiedCount' => Lead::query()->where('company_id', $companyId)->where('status', 'qualified')->count(),
            'convertedCount' => Lead::query()->where('company_id', $companyId)->where('status', 'converted')->count(),
        ]);
    }

    public function convert(Lead $lead): RedirectResponse
    {
        abort_unless($lead->company_id === Company::current()->id, 404);
        abort_unless($lead->status === 'qualified', 422, 'Only qualified leads can be converted.');

        $contact = Contact::create([
            'company_id' => $lead->company_id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company_name' => $lead->company_name,
        ]);

        $lead->update([
            'status' => 'converted',
            'converted_at' => now(),
            'converted_contact_id' => $contact->id,
        ]);

        return back()->with('status', "{$lead->name} converted to a contact.");
    }
}
