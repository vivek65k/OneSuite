<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Activity;
use App\Models\Crm\Note;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiConversationSummaryController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $accountId = $request->query('account_id');
        $accounts = Account::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);

        $summary = null;
        if ($accountId) {
            $account = Account::where('company_id', $companyId)->find($accountId);
            if ($account) {
                $activities = Activity::where('company_id', $companyId)->where('related_type', Account::class)
                    ->where('related_id', $account->id)->orderByDesc('created_at')->get();
                $notes = Note::where('company_id', $companyId)->where('notable_type', Account::class)
                    ->where('notable_id', $account->id)->orderByDesc('created_at')->get();

                $byType = $activities->groupBy('type')->map->count();
                $last = $activities->first();
                $pendingTasks = $activities->where('type', 'task')->where('status', 'pending')->count();

                $lines = [];
                $lines[] = $activities->count().' interaction(s) logged with '.$account->name.' ('.$byType->map(fn ($c, $t) => "{$c} {$t}")->implode(', ').').';
                if ($last) {
                    $lines[] = 'Most recent: '.$last->subject.' on '.$last->created_at->format('M j, Y').' by '.($last->owner?->name ?? 'unknown').'.';
                }
                if ($pendingTasks > 0) {
                    $lines[] = "{$pendingTasks} task(s) still pending for this account.";
                }
                if ($notes->isNotEmpty()) {
                    $lines[] = $notes->count().' note(s) on file. Latest: "'.\Illuminate\Support\Str::limit($notes->first()->body, 100).'"';
                }
                if (empty($lines)) {
                    $lines[] = 'No interactions recorded yet for this account.';
                }

                $summary = ['account' => $account, 'lines' => $lines, 'activities' => $activities->take(10), 'notes' => $notes->take(5)];
            }
        }

        return view('admin.crm.ai-conversation-summary.index', [
            'accounts' => $accounts,
            'accountId' => $accountId,
            'summary' => $summary,
        ]);
    }
}
