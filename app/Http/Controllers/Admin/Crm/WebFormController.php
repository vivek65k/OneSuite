<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Campaign;
use App\Models\Crm\Lead;
use App\Models\Crm\WebForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebFormController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));

        $query = WebForm::where('company_id', $companyId)->with('campaign')->withCount('submissions');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $forms = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.crm.web-forms.index', [
            'forms' => $forms,
            'search' => $search,
            'totalSubmissions' => WebForm::where('company_id', $companyId)->sum('submissions_count'),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.web-forms.form', [
            'webForm' => new WebForm(['fields_json' => [
                ['name' => 'name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'required' => false],
            ]]),
            'campaigns' => Campaign::where('company_id', Company::current()->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $webForm = WebForm::create($data + ['company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.web-forms.edit', $webForm)->with('status', 'Web form created.');
    }

    public function edit(WebForm $webForm): View
    {
        abort_unless($webForm->company_id === Company::current()->id, 404);

        return view('admin.crm.web-forms.form', [
            'webForm' => $webForm,
            'campaigns' => Campaign::where('company_id', $webForm->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, WebForm $webForm): RedirectResponse
    {
        abort_unless($webForm->company_id === Company::current()->id, 404);
        $webForm->update($this->validated($request));

        return redirect()->route('admin.crm.web-forms.edit', $webForm)->with('status', 'Web form updated.');
    }

    public function destroy(WebForm $webForm): RedirectResponse
    {
        abort_unless($webForm->company_id === Company::current()->id, 404);
        $webForm->delete();

        return redirect()->route('admin.crm.web-forms.index')->with('status', 'Web form deleted.');
    }

    public function showPublic(string $slug): View
    {
        $webForm = WebForm::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('crm.public-web-form', ['webForm' => $webForm]);
    }

    public function submitPublic(Request $request, string $slug): RedirectResponse
    {
        $webForm = WebForm::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $rules = [];
        foreach ($webForm->fields_json ?? [] as $field) {
            $rules[$field['name']] = [($field['required'] ?? false) ? 'required' : 'nullable', 'string', 'max:500'];
        }

        $data = $request->validate($rules);

        $lead = Lead::create([
            'company_id' => $webForm->company_id,
            'name' => $data['name'] ?? 'Web Form Submission',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'campaign_id' => $webForm->campaign_id,
            'status' => 'new',
        ]);

        $webForm->submissions()->create(['data' => $data, 'lead_id' => $lead->id]);
        $webForm->increment('submissions_count');

        if ($webForm->campaign_id) {
            \App\Models\Crm\CampaignMember::firstOrCreate([
                'campaign_id' => $webForm->campaign_id,
                'member_type' => Lead::class,
                'member_id' => $lead->id,
            ], ['company_id' => $webForm->company_id, 'status' => 'converted', 'added_at' => now()]);
        }

        if ($webForm->redirect_url) {
            return redirect()->away($webForm->redirect_url);
        }

        return redirect()->route('crm.public.web-forms.show', $slug)->with('submitted', true);
    }

    protected function validated(Request $request): array
    {
        if ($request->filled('fields_json') && is_string($request->input('fields_json'))) {
            $request->merge(['fields_json' => json_decode($request->input('fields_json'), true) ?? []]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'campaign_id' => ['nullable', 'exists:crm_campaigns,id'],
            'fields_json' => ['nullable', 'array'],
            'fields_json.*.name' => ['required_with:fields_json', 'string'],
            'fields_json.*.label' => ['nullable', 'string'],
            'fields_json.*.type' => ['nullable', 'string'],
            'fields_json.*.required' => ['nullable', 'boolean'],
            'redirect_url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'form';
        $slug = $base;
        $i = 1;
        while (WebForm::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
