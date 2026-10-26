<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Contact;
use App\Models\Crm\CustomerFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $ratingFilter = (string) $request->query('rating', '');

        $query = CustomerFeedback::where('company_id', $companyId)->with(['account', 'contact']);

        if ($ratingFilter !== '') {
            $query->where('rating', $ratingFilter);
        }

        $feedback = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $all = CustomerFeedback::where('company_id', $companyId)->get();

        return view('admin.crm.customer-feedback.index', [
            'feedback' => $feedback,
            'ratingFilter' => $ratingFilter,
            'accounts' => Account::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'avgRating' => $all->count() > 0 ? round($all->avg('rating'), 1) : 0,
            'totalFeedback' => $all->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['nullable', 'exists:crm_accounts,id'],
            'contact_id' => ['nullable', 'exists:crm_contacts,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'source' => ['nullable', 'string', 'max:100'],
            'comment' => ['nullable', 'string'],
        ]);

        CustomerFeedback::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Feedback recorded.');
    }

    public function destroy(CustomerFeedback $customerFeedback): RedirectResponse
    {
        abort_unless($customerFeedback->company_id === Company::current()->id, 404);
        $customerFeedback->delete();

        return back()->with('status', 'Feedback removed.');
    }
}
