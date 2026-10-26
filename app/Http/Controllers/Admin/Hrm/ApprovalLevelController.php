<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\ApprovalLevelSetting;
use App\Models\Company;
use App\Support\ApprovalFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * HRM > Settings > Approval Levels: how many managers up the reporting
 * chain each kind of request has to pass (see App\Support\ApprovalFlow).
 * Gated on the existing approval-rule permissions rather than new ones -
 * it is the same "who approves what" configuration.
 */
class ApprovalLevelController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('view_any_approval_rule'), 403);

        $companyId = Company::current()->id;

        return view('admin.hrm.settings.approval-levels', [
            'types' => collect(ApprovalFlow::TYPES)->map(fn (array $type, string $key) => $type + [
                'levels' => ApprovalFlow::configuredLevels($companyId, $key),
            ]),
            'maxLevels' => ApprovalFlow::MAX_LEVELS,
            'canEdit' => $request->user()->can('update_approval_rule'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('update_approval_rule'), 403);

        $data = $request->validate([
            'levels' => ['required', 'array'],
            'levels.*' => ['required', 'integer', 'min:1', 'max:'.ApprovalFlow::MAX_LEVELS],
        ]);

        $companyId = Company::current()->id;

        foreach (array_intersect_key($data['levels'], ApprovalFlow::TYPES) as $type => $levels) {
            ApprovalLevelSetting::updateOrCreate(
                ['company_id' => $companyId, 'request_type' => $type],
                ['levels' => (int) $levels],
            );
        }

        return redirect()->route('admin.approval-levels.index')
            ->with('status', 'Approval levels saved. They apply to requests still waiting as well as new ones.');
    }
}
