<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\EmailSequence;
use App\Models\Crm\EmailSequenceEnrollment;
use App\Models\Crm\EmailSequenceStep;
use App\Models\Crm\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailSequenceController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.email-sequences.index', [
            'sequences' => EmailSequence::where('company_id', $companyId)->withCount(['steps', 'enrollments'])->orderByDesc('created_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.email-sequences.form', ['sequence' => new EmailSequence, 'steps' => collect()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $sequence = EmailSequence::create(['name' => $data['name'], 'company_id' => Company::current()->id, 'is_active' => true]);
        $this->saveSteps($sequence, $data['steps']);

        return redirect()->route('admin.crm.email-sequences.edit', $sequence)->with('status', 'Sequence created.');
    }

    public function edit(EmailSequence $emailSequence): View
    {
        abort_unless($emailSequence->company_id === Company::current()->id, 404);

        return view('admin.crm.email-sequences.form', ['sequence' => $emailSequence, 'steps' => $emailSequence->steps]);
    }

    public function update(Request $request, EmailSequence $emailSequence): RedirectResponse
    {
        abort_unless($emailSequence->company_id === Company::current()->id, 404);
        $data = $this->validated($request);
        $emailSequence->update(['name' => $data['name']]);
        $emailSequence->steps()->delete();
        $this->saveSteps($emailSequence, $data['steps']);

        return redirect()->route('admin.crm.email-sequences.edit', $emailSequence)->with('status', 'Sequence updated.');
    }

    public function destroy(EmailSequence $emailSequence): RedirectResponse
    {
        abort_unless($emailSequence->company_id === Company::current()->id, 404);
        $emailSequence->delete();

        return redirect()->route('admin.crm.email-sequences.index')->with('status', 'Sequence deleted.');
    }

    public function enroll(Request $request, EmailSequence $emailSequence): RedirectResponse
    {
        abort_unless($emailSequence->company_id === Company::current()->id, 404);
        $data = $request->validate(['lead_id' => ['required', 'exists:crm_leads,id']]);

        $firstStep = $emailSequence->steps()->first();

        EmailSequenceEnrollment::firstOrCreate(
            ['sequence_id' => $emailSequence->id, 'lead_id' => $data['lead_id']],
            [
                'company_id' => $emailSequence->company_id, 'current_step' => 0, 'status' => 'active',
                'enrolled_at' => now(), 'next_step_due_at' => $firstStep ? now()->addDays($firstStep->delay_days) : null,
            ]
        );

        return back()->with('status', 'Lead enrolled.');
    }

    public function advance(EmailSequenceEnrollment $enrollment): RedirectResponse
    {
        abort_unless($enrollment->company_id === Company::current()->id, 404);
        $sequence = $enrollment->sequence;
        $nextStep = $sequence->steps()->where('sort_order', $enrollment->current_step + 1)->first();

        if ($nextStep) {
            $enrollment->update(['current_step' => $enrollment->current_step + 1, 'next_step_due_at' => now()->addDays($nextStep->delay_days)]);
            $message = "Advanced to step {$nextStep->sort_order}: {$nextStep->subject}";
        } else {
            $enrollment->update(['status' => 'completed', 'next_step_due_at' => null]);
            $message = 'Sequence completed.';
        }

        return back()->with('status', $message);
    }

    public function stopEnrollment(EmailSequenceEnrollment $enrollment): RedirectResponse
    {
        abort_unless($enrollment->company_id === Company::current()->id, 404);
        $enrollment->update(['status' => 'stopped']);

        return back()->with('status', 'Enrollment stopped.');
    }

    protected function validated(Request $request): array
    {
        if ($request->filled('steps_json') && is_string($request->input('steps_json'))) {
            $request->merge(['steps' => json_decode($request->input('steps_json'), true) ?? []]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'steps' => ['nullable', 'array'],
            'steps.*.subject' => ['required_with:steps', 'string'],
            'steps.*.delay_days' => ['nullable', 'integer', 'min:0'],
            'steps.*.body' => ['nullable', 'string'],
        ]) + ['steps' => $request->input('steps', [])];
    }

    protected function saveSteps(EmailSequence $sequence, array $steps): void
    {
        foreach ($steps as $i => $step) {
            EmailSequenceStep::create([
                'sequence_id' => $sequence->id,
                'sort_order' => $i,
                'delay_days' => $step['delay_days'] ?? 1,
                'subject' => $step['subject'],
                'body' => $step['body'] ?? null,
            ]);
        }
    }
}
