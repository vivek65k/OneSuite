<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Admin\Crm\Concerns\ManagesAutomationRules;
use App\Http\Controllers\Controller;

class EscalationRuleController extends Controller
{
    use ManagesAutomationRules;

    protected function category(): string
    {
        return 'escalation';
    }

    protected function viewPrefix(): string
    {
        return 'admin.crm.escalation-rules';
    }

    protected function indexRoute(): string
    {
        return 'admin.crm.escalation-rules.index';
    }
}
