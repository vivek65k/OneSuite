<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Admin\Crm\Concerns\ManagesAutomationRules;
use App\Http\Controllers\Controller;

class TriggerActionController extends Controller
{
    use ManagesAutomationRules;

    protected function category(): string
    {
        return 'trigger';
    }

    protected function viewPrefix(): string
    {
        return 'admin.crm.trigger-actions';
    }

    protected function indexRoute(): string
    {
        return 'admin.crm.trigger-actions.index';
    }
}
