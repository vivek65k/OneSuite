<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Dispatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryConfirmationController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.delivery-confirmations.index', [
            'pending' => Dispatch::where('company_id', $companyId)->where('status', 'dispatched')->with(['warehouse', 'package.lines.item'])->orderBy('dispatched_at')->get(),
            'confirmed' => Dispatch::where('company_id', $companyId)->where('status', 'delivered')->with(['warehouse', 'package'])->orderByDesc('delivered_at')->limit(20)->get(),
        ]);
    }

    public function confirm(Request $request, Dispatch $dispatch): RedirectResponse
    {
        abort_unless($dispatch->company_id === Company::current()->id, 404);
        abort_unless($dispatch->status === 'dispatched', 422, 'This dispatch is already confirmed.');

        $data = $request->validate([
            'received_by' => ['required', 'string', 'max:150'],
            'signature_name' => ['nullable', 'string', 'max:150'],
        ]);

        $dispatch->update($data + ['status' => 'delivered', 'delivered_at' => now()]);

        return back()->with('status', "Dispatch {$dispatch->dispatch_number} confirmed as delivered.");
    }
}
