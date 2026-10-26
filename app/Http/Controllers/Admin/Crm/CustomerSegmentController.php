<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CustomerSegment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerSegmentController extends Controller
{
    protected const SORTABLE = ['name', 'accounts_count', 'contacts_count'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = CustomerSegment::where('company_id', Company::current()->id)->withCount(['accounts', 'contacts']);

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $segments = $query->orderBy($sort, $direction)->get();

        $allSegments = CustomerSegment::where('company_id', Company::current()->id)->withCount(['accounts', 'contacts'])->get();

        return view('admin.crm.customer-segments.index', [
            'segments' => $segments,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'totalSegments' => $allSegments->count(),
            'totalAccounts' => $allSegments->sum('accounts_count'),
            'totalContacts' => $allSegments->sum('contacts_count'),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.customer-segments.form', ['segment' => new CustomerSegment]);
    }

    public function store(Request $request): RedirectResponse
    {
        CustomerSegment::create($this->validated($request) + ['company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.customer-segments.index')->with('status', 'Segment created.');
    }

    public function edit(CustomerSegment $customerSegment): View
    {
        abort_unless($customerSegment->company_id === Company::current()->id, 404);

        return view('admin.crm.customer-segments.form', ['segment' => $customerSegment]);
    }

    public function update(Request $request, CustomerSegment $customerSegment): RedirectResponse
    {
        abort_unless($customerSegment->company_id === Company::current()->id, 404);

        $customerSegment->update($this->validated($request));

        return redirect()->route('admin.crm.customer-segments.index')->with('status', 'Segment updated.');
    }

    public function destroy(CustomerSegment $customerSegment): RedirectResponse
    {
        abort_unless($customerSegment->company_id === Company::current()->id, 404);
        $customerSegment->delete();

        return back()->with('status', 'Segment removed.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
