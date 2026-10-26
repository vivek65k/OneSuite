<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Activity;
use App\Models\Crm\CaseModel;
use App\Models\Crm\CustomerFeedback;
use Illuminate\View\View;

class AiChurnPredictionController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;
        $accounts = Account::where('company_id', $companyId)->get();

        $rows = $accounts->map(function ($account) use ($companyId) {
            $risk = 0;
            $factors = [];

            $lastActivity = Activity::where('company_id', $companyId)->where('related_type', Account::class)
                ->where('related_id', $account->id)->orderByDesc('created_at')->first();
            if (! $lastActivity || $lastActivity->created_at->diffInDays(now()) > 30) {
                $risk += 30;
                $factors[] = 'No contact in 30+ days';
            }

            $openCases = CaseModel::where('company_id', $companyId)->where('account_id', $account->id)
                ->whereNotIn('status', ['resolved', 'closed'])->count();
            if ($openCases > 0) {
                $risk += min($openCases * 15, 30);
                $factors[] = "{$openCases} open case(s)";
            }

            $avgRating = CustomerFeedback::where('company_id', $companyId)->where('account_id', $account->id)->avg('rating');
            if ($avgRating !== null && $avgRating <= 2.5) {
                $risk += 25;
                $factors[] = 'Low satisfaction rating ('.round($avgRating, 1).')';
            }

            $escalated = CaseModel::where('company_id', $companyId)->where('account_id', $account->id)->where('is_escalated', true)->count();
            if ($escalated > 0) {
                $risk += 15;
                $factors[] = "{$escalated} escalated case(s)";
            }

            return ['account' => $account, 'risk' => min($risk, 100), 'factors' => $factors];
        })->sortByDesc('risk')->values();

        return view('admin.crm.ai-churn-prediction.index', [
            'rows' => $rows,
            'highRiskCount' => $rows->where('risk', '>=', 50)->count(),
        ]);
    }
}
