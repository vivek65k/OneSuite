<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmailTemplate;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmailTemplateController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'subject', 'is_active', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', EmailTemplate::class);

        $state = $this->resolveTableState($request, 'email-templates', [
            'sort' => 'name', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'name', self::SORTABLE, true) ? $state['sort'] : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $templates = $this->filteredQuery($state)
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.settings.workflow-approvals.email-templates.index', [
            'templates' => $templates,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', EmailTemplate::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'name';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $templates = $this->filteredQuery($state)->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'email-templates-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Subject', 'Active'],
            (function () use ($templates) {
                foreach ($templates as $template) {
                    yield [$template->name, $template->subject, $template->is_active ? 'Yes' : 'No'];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return EmailTemplate::query()
            ->where('company_id', Company::current()->id)
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('subject', 'like', "%{$search}%");
            }));
    }

    public function create(): View
    {
        $this->authorize('create', EmailTemplate::class);

        return view('admin.settings.workflow-approvals.email-templates.create', ['template' => new EmailTemplate()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmailTemplate::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        EmailTemplate::create($data);

        return redirect()->route('admin.email-templates.index')->with('status', 'Email template created.');
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        $this->authorize('update', $emailTemplate);

        return view('admin.settings.workflow-approvals.email-templates.edit', ['template' => $emailTemplate]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorize('update', $emailTemplate);

        $emailTemplate->update($this->validated($request));

        return redirect()->route('admin.email-templates.index')->with('status', 'Email template updated.');
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorize('delete', $emailTemplate);

        $emailTemplate->delete();

        return redirect()->route('admin.email-templates.index')->with('status', 'Email template deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
