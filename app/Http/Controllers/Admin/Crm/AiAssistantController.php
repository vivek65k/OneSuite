<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Contact;
use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public const TEMPLATES = [
        'intro' => 'Introduction Email',
        'follow_up' => 'Follow-up Email',
        'proposal' => 'Proposal Summary',
    ];

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.ai-assistant.index', [
            'leads' => Lead::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'opportunities' => Opportunity::where('company_id', $companyId)->where('status', 'open')->orderBy('name')->get(['id', 'name', 'amount']),
            'templates' => self::TEMPLATES,
            'draft' => null,
        ]);
    }

    public function generate(Request $request): View
    {
        $data = $request->validate([
            'template' => ['required', 'in:'.implode(',', array_keys(self::TEMPLATES))],
            'recipient_type' => ['required', 'in:lead,contact,opportunity'],
            'recipient_id' => ['required', 'integer'],
        ]);

        $companyId = Company::current()->id;
        $name = 'there';
        $context = '';

        if ($data['recipient_type'] === 'lead') {
            $record = Lead::where('company_id', $companyId)->find($data['recipient_id']);
            $name = $record?->name ?? $name;
            $context = $record?->company_name ?? '';
        } elseif ($data['recipient_type'] === 'contact') {
            $record = Contact::where('company_id', $companyId)->find($data['recipient_id']);
            $name = $record?->name ?? $name;
            $context = $record?->company_name ?? '';
        } else {
            $record = Opportunity::where('company_id', $companyId)->find($data['recipient_id']);
            $name = $record?->name ?? $name;
            $context = $record ? number_format($record->amount, 0) : '';
        }

        $draft = match ($data['template']) {
            'intro' => [
                'subject' => "Great connecting with you, {$name}",
                'body' => "Hi {$name},\n\nIt was great learning about ".($context ?: 'your needs').". I'd love to set up a short call this week to understand your goals and share how we can help.\n\nWould a 15-minute call work sometime in the next couple of days?\n\nBest regards",
            ],
            'follow_up' => [
                'subject' => "Following up, {$name}",
                'body' => "Hi {$name},\n\nJust following up on our last conversation".($context ? " about {$context}" : '').". Did you have a chance to review the details? Happy to answer any questions or hop on a quick call.\n\nLooking forward to hearing from you.",
            ],
            default => [
                'subject' => "Proposal summary for {$name}",
                'body' => "Hi {$name},\n\nThank you for the opportunity to put together a proposal".($context ? " valued at {$context}" : '').". Attached is a summary of the scope, timeline and investment we discussed. Let me know if anything needs adjusting before we finalize.\n\nLooking forward to your feedback.",
            ],
        };

        return view('admin.crm.ai-assistant.index', [
            'leads' => Lead::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'opportunities' => Opportunity::where('company_id', $companyId)->where('status', 'open')->orderBy('name')->get(['id', 'name', 'amount']),
            'templates' => self::TEMPLATES,
            'draft' => $draft,
            'selected' => $data,
        ]);
    }
}
