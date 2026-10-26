<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\Crm\ResolvesRelatable;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpReminderController extends Controller
{
    use ResolvesRelatable;

    /**
     * Any pending activity (task, call, or meeting) with a reminder_at set
     * is a follow-up - this is a cross-type queue over Activity, not a
     * separate table, so marking it done here is the same as completing
     * the underlying task/call/meeting.
     */
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $when = (string) $request->query('when', 'due');

        $query = Activity::where('company_id', $companyId)
            ->where('status', 'pending')
            ->whereNotNull('reminder_at')
            ->with('owner');

        if ($when === 'due') {
            $query->where('reminder_at', '<=', now());
        } elseif ($when === 'upcoming') {
            $query->where('reminder_at', '>', now());
        }

        $reminders = $query->orderBy('reminder_at')->paginate(25)->withQueryString();

        return view('admin.crm.follow-up-reminders.index', [
            'reminders' => $reminders->through(fn ($r) => tap($r, fn ($x) => $x->relatedLabel = $this->relatedLabel($x->related_type, $x->related_id))),
            'when' => $when,
            'dueCount' => Activity::where('company_id', $companyId)->where('status', 'pending')->whereNotNull('reminder_at')->where('reminder_at', '<=', now())->count(),
            'upcomingCount' => Activity::where('company_id', $companyId)->where('status', 'pending')->whereNotNull('reminder_at')->where('reminder_at', '>', now())->count(),
        ]);
    }

    public function complete(Activity $reminder): RedirectResponse
    {
        abort_unless($reminder->company_id === Company::current()->id, 404);
        $reminder->update(['status' => 'completed', 'completed_at' => now()]);

        return back()->with('status', 'Marked complete.');
    }

    public function snooze(Request $request, Activity $reminder): RedirectResponse
    {
        abort_unless($reminder->company_id === Company::current()->id, 404);

        $days = (int) $request->input('days', 1);
        $reminder->update(['reminder_at' => now()->addDays(max(1, $days))]);

        return back()->with('status', 'Snoozed for '.max(1, $days).' day(s).');
    }
}
