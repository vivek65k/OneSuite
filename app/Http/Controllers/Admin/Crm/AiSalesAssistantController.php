<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use Illuminate\View\View;

class AiSalesAssistantController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        $hotLeads = Lead::where('company_id', $companyId)->where('score', '>=', 70)
            ->whereNotIn('status', ['converted', 'unqualified'])->orderByDesc('score')->limit(5)->get();

        $followUpsDueToday = Activity::where('company_id', $companyId)->whereIn('type', ['task', 'call', 'meeting'])
            ->where('status', 'pending')->whereDate('due_at', now()->toDateString())->get();

        $stalledDeals = Opportunity::where('company_id', $companyId)->where('status', 'open')
            ->where('updated_at', '<=', now()->subDays(14))->orderBy('updated_at')->limit(5)->get();

        $closingSoon = Opportunity::where('company_id', $companyId)->where('status', 'open')
            ->whereNotNull('expected_close_date')->whereBetween('expected_close_date', [now(), now()->addDays(7)])
            ->orderBy('expected_close_date')->get();

        return view('admin.crm.ai-sales-assistant.index', [
            'hotLeads' => $hotLeads,
            'followUpsDueToday' => $followUpsDueToday,
            'stalledDeals' => $stalledDeals,
            'closingSoon' => $closingSoon,
            'closingSoonTotal' => $closingSoon->sum('amount'),
        ]);
    }
}
