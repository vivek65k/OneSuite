<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Contact;
use App\Models\Crm\ContactRelationship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactRelationshipController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('relationship_type', '');

        $query = ContactRelationship::where('company_id', $companyId)->with(['contact', 'relatedContact']);

        if ($type !== '') {
            $query->where('relationship_type', $type);
        }

        if ($search !== '') {
            $query->where(fn ($q) => $q->whereHas('contact', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                ->orWhereHas('relatedContact', fn ($c) => $c->where('name', 'like', "%{$search}%")));
        }

        return view('admin.crm.contact-relationships.index', [
            'relationships' => $query->latest()->paginate(25)->withQueryString(),
            'search' => $search,
            'type' => $type,
            'total' => ContactRelationship::where('company_id', $companyId)->count(),
            'contacts' => Contact::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'contact_id' => ['required', 'exists:crm_contacts,id', 'different:related_contact_id'],
            'related_contact_id' => ['required', 'exists:crm_contacts,id'],
            'relationship_type' => ['required', 'string', 'in:'.implode(',', array_keys(ContactRelationship::TYPES))],
        ]);

        foreach ([$data['contact_id'], $data['related_contact_id']] as $id) {
            abort_unless(Contact::where('id', $id)->where('company_id', $companyId)->exists(), 404);
        }

        ContactRelationship::firstOrCreate(
            ['company_id' => $companyId, 'contact_id' => $data['contact_id'], 'related_contact_id' => $data['related_contact_id']],
            ['relationship_type' => $data['relationship_type']]
        );

        return back()->with('status', 'Relationship added.');
    }

    public function destroy(ContactRelationship $contactRelationship): RedirectResponse
    {
        abort_unless($contactRelationship->company_id === Company::current()->id, 404);
        $contactRelationship->delete();

        return back()->with('status', 'Relationship removed.');
    }
}
