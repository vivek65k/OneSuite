<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\NotificationRule;
use App\Models\SmsTemplate;
use App\Models\WhatsappTemplate;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationRuleController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['event', 'is_active', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', NotificationRule::class);

        $state = $this->resolveTableState($request, 'notification-rules', [
            'sort' => 'event', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'event', self::SORTABLE, true) ? $state['sort'] : 'event';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $rules = $this->filteredQuery($state)
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.settings.workflow-approvals.notification-rules.index', [
            'rules' => $rules,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', NotificationRule::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'event';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'event';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $rules = $this->filteredQuery($state)->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'notification-rules-'.now()->format('Y-m-d-His').'.csv',
            ['Event', 'Email', 'SMS', 'WhatsApp', 'In-app', 'Active'],
            (function () use ($rules) {
                foreach ($rules as $rule) {
                    yield [
                        $rule->event,
                        $rule->via_email ? 'Yes' : 'No',
                        $rule->via_sms ? 'Yes' : 'No',
                        $rule->via_whatsapp ? 'Yes' : 'No',
                        $rule->via_in_app ? 'Yes' : 'No',
                        $rule->is_active ? 'Yes' : 'No',
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return NotificationRule::query()
            ->where('company_id', Company::current()->id)
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where('event', 'like', "%{$search}%"));
    }

    public function create(): View
    {
        $this->authorize('create', NotificationRule::class);

        return view('admin.settings.workflow-approvals.notification-rules.create', array_merge(
            ['rule' => new NotificationRule()],
            $this->templateOptions()
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', NotificationRule::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        NotificationRule::create($data);

        return redirect()->route('admin.notification-rules.index')->with('status', 'Notification rule created.');
    }

    public function edit(NotificationRule $notificationRule): View
    {
        $this->authorize('update', $notificationRule);

        return view('admin.settings.workflow-approvals.notification-rules.edit', array_merge(
            ['rule' => $notificationRule],
            $this->templateOptions()
        ));
    }

    public function update(Request $request, NotificationRule $notificationRule): RedirectResponse
    {
        $this->authorize('update', $notificationRule);

        $notificationRule->update($this->validated($request));

        return redirect()->route('admin.notification-rules.index')->with('status', 'Notification rule updated.');
    }

    public function destroy(NotificationRule $notificationRule): RedirectResponse
    {
        $this->authorize('delete', $notificationRule);

        $notificationRule->delete();

        return redirect()->route('admin.notification-rules.index')->with('status', 'Notification rule deleted.');
    }

    protected function templateOptions(): array
    {
        $companyId = Company::current()->id;

        return [
            'emailTemplates' => EmailTemplate::query()->where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
            'smsTemplates' => SmsTemplate::query()->where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
            'whatsappTemplates' => WhatsappTemplate::query()->where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
        ];
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'event' => ['required', 'string', 'max:255'],
            'email_template_id' => ['nullable', 'exists:email_templates,id'],
            'sms_template_id' => ['nullable', 'exists:sms_templates,id'],
            'whatsapp_template_id' => ['nullable', 'exists:whatsapp_templates,id'],
        ]);

        $data['via_email'] = $request->boolean('via_email');
        $data['via_sms'] = $request->boolean('via_sms');
        $data['via_whatsapp'] = $request->boolean('via_whatsapp');
        $data['via_in_app'] = $request->boolean('via_in_app');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
