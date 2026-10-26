<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Admin\Crm\Concerns\ManagesAutomationRules;
use App\Http\Controllers\Controller;

class AssignmentRuleController extends Controller
{
    use ManagesAutomationRules;

    protected function category(): string
    {
        return 'assignment';
    }

    protected function viewPrefix(): string
    {
        return 'admin.crm.assignment-rules';
    }

    protected function indexRoute(): string
    {
        return 'admin.crm.assignment-rules.index';
    }
}
