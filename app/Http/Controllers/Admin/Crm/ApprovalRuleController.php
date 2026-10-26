<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Admin\Crm\Concerns\ManagesAutomationRules;
use App\Http\Controllers\Controller;

class ApprovalRuleController extends Controller
{
    use ManagesAutomationRules;

    protected function category(): string
    {
        return 'approval';
    }

    protected function viewPrefix(): string
    {
        return 'admin.crm.approval-rules';
    }

    protected function indexRoute(): string
    {
        return 'admin.crm.approval-rules.index';
    }
}
