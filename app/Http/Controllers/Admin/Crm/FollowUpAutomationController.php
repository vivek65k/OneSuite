<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Admin\Crm\Concerns\ManagesAutomationRules;
use App\Http\Controllers\Controller;

class FollowUpAutomationController extends Controller
{
    use ManagesAutomationRules;

    protected function category(): string
    {
        return 'follow_up';
    }

    protected function viewPrefix(): string
    {
        return 'admin.crm.follow-up-automation';
    }

    protected function indexRoute(): string
    {
        return 'admin.crm.follow-up-automation.index';
    }
}
