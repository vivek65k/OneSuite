<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;

        try {
            $month = Carbon::createFromFormat('Y-m-d', $request->query('month', now()->format('Y-m')).'-01')->startOfMonth();
        } catch (\Exception) {
            $month = now()->startOfMonth();
        }

        $gridStart = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $activities = Activity::where('company_id', $companyId)
            ->whereIn('type', ['task', 'call', 'meeting'])
            ->where(function ($q) use ($gridStart, $gridEnd) {
                $q->whereBetween('due_at', [$gridStart, $gridEnd])
                    ->orWhereBetween('start_at', [$gridStart, $gridEnd]);
            })
            ->with('owner')
            ->get()
            ->groupBy(fn ($a) => ($a->start_at ?? $a->due_at)?->toDateString());

        $weeks = collect();
        $cursor = $gridStart->copy();

        while ($cursor <= $gridEnd) {
            $week = collect();

            for ($i = 0; $i < 7; $i++) {
                $week->push(['date' => $cursor->copy(), 'items' => $activities->get($cursor->toDateString(), collect())]);
                $cursor->addDay();
            }

            $weeks->push($week);
        }

        return view('admin.crm.calendar.index', [
            'month' => $month,
            'weeks' => $weeks,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'todayStr' => now()->toDateString(),
        ]);
    }
}
