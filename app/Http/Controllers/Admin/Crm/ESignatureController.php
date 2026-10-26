<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Contract;
use App\Models\Crm\ESignatureRequest;
use App\Models\Crm\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ESignatureController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        $requests = ESignatureRequest::where('company_id', $companyId)->with('signable')->latest()->paginate(25);

        return view('admin.crm.esignatures.index', [
            'requests' => $requests,
            'total' => ESignatureRequest::where('company_id', $companyId)->count(),
            'pendingCount' => ESignatureRequest::where('company_id', $companyId)->where('status', 'pending')->count(),
            'signedCount' => ESignatureRequest::where('company_id', $companyId)->where('status', 'signed')->count(),
            'contracts' => Contract::where('company_id', $companyId)->orderBy('title')->pluck('title', 'id'),
            'proposals' => Proposal::where('company_id', $companyId)->orderBy('title')->pluck('title', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'signable_type' => ['required', 'in:contract,proposal'],
            'signable_id' => ['required', 'integer'],
            'signer_name' => ['required', 'string', 'max:255'],
            'signer_email' => ['required', 'email', 'max:255'],
        ]);

        $modelClass = $data['signable_type'] === 'contract' ? Contract::class : Proposal::class;
        abort_unless($modelClass::where('company_id', $companyId)->where('id', $data['signable_id'])->exists(), 404);

        ESignatureRequest::create([
            'company_id' => $companyId,
            'signable_type' => $modelClass,
            'signable_id' => $data['signable_id'],
            'signer_name' => $data['signer_name'],
            'signer_email' => $data['signer_email'],
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        return back()->with('status', 'Signature request sent to '.$data['signer_name'].'.');
    }

    public function markSigned(ESignatureRequest $esignatureRequest): RedirectResponse
    {
        abort_unless($esignatureRequest->company_id === Company::current()->id, 404);
        $esignatureRequest->update(['status' => 'signed', 'signed_at' => now()]);

        return back()->with('status', 'Marked as signed.');
    }

    public function markDeclined(ESignatureRequest $esignatureRequest): RedirectResponse
    {
        abort_unless($esignatureRequest->company_id === Company::current()->id, 404);
        $esignatureRequest->update(['status' => 'declined']);

        return back()->with('status', 'Marked as declined.');
    }

    public function destroy(ESignatureRequest $esignatureRequest): RedirectResponse
    {
        abort_unless($esignatureRequest->company_id === Company::current()->id, 404);
        $esignatureRequest->delete();

        return back()->with('status', 'Request removed.');
    }
}
