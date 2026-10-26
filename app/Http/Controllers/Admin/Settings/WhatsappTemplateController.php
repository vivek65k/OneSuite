<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\WhatsappTemplate;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhatsappTemplateController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'is_active', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', WhatsappTemplate::class);

        $state = $this->resolveTableState($request, 'whatsapp-templates', [
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

        return view('admin.settings.workflow-approvals.whatsapp-templates.index', [
            'templates' => $templates,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', WhatsappTemplate::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'name';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $templates = $this->filteredQuery($state)->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'whatsapp-templates-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Body', 'Active'],
            (function () use ($templates) {
                foreach ($templates as $template) {
                    yield [$template->name, $template->body, $template->is_active ? 'Yes' : 'No'];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return WhatsappTemplate::query()
            ->where('company_id', Company::current()->id)
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"));
    }

    public function create(): View
    {
        $this->authorize('create', WhatsappTemplate::class);

        return view('admin.settings.workflow-approvals.whatsapp-templates.create', ['template' => new WhatsappTemplate()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', WhatsappTemplate::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        WhatsappTemplate::create($data);

        return redirect()->route('admin.whatsapp-templates.index')->with('status', 'WhatsApp template created.');
    }

    public function edit(WhatsappTemplate $whatsappTemplate): View
    {
        $this->authorize('update', $whatsappTemplate);

        return view('admin.settings.workflow-approvals.whatsapp-templates.edit', ['template' => $whatsappTemplate]);
    }

    public function update(Request $request, WhatsappTemplate $whatsappTemplate): RedirectResponse
    {
        $this->authorize('update', $whatsappTemplate);

        $whatsappTemplate->update($this->validated($request));

        return redirect()->route('admin.whatsapp-templates.index')->with('status', 'WhatsApp template updated.');
    }

    public function destroy(WhatsappTemplate $whatsappTemplate): RedirectResponse
    {
        $this->authorize('delete', $whatsappTemplate);

        $whatsappTemplate->delete();

        return redirect()->route('admin.whatsapp-templates.index')->with('status', 'WhatsApp template deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1600'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
