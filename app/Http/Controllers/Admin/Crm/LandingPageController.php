<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Campaign;
use App\Models\Crm\LandingPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');

        $query = LandingPage::where('company_id', $companyId)->with('campaign');

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $pages = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $all = LandingPage::where('company_id', $companyId)->get();

        return view('admin.crm.landing-pages.index', [
            'pages' => $pages,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'statuses' => LandingPage::STATUSES,
            'totalViews' => $all->sum('views_count'),
            'totalConversions' => $all->sum('conversions_count'),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.landing-pages.form', [
            'page' => new LandingPage,
            'campaigns' => Campaign::where('company_id', Company::current()->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $page = LandingPage::create($data + ['company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.landing-pages.edit', $page)->with('status', 'Landing page created.');
    }

    public function edit(LandingPage $landingPage): View
    {
        abort_unless($landingPage->company_id === Company::current()->id, 404);

        return view('admin.crm.landing-pages.form', [
            'page' => $landingPage,
            'campaigns' => Campaign::where('company_id', $landingPage->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, LandingPage $landingPage): RedirectResponse
    {
        abort_unless($landingPage->company_id === Company::current()->id, 404);
        $landingPage->update($this->validated($request));

        return redirect()->route('admin.crm.landing-pages.edit', $landingPage)->with('status', 'Landing page updated.');
    }

    public function destroy(LandingPage $landingPage): RedirectResponse
    {
        abort_unless($landingPage->company_id === Company::current()->id, 404);
        $landingPage->delete();

        return redirect()->route('admin.crm.landing-pages.index')->with('status', 'Landing page deleted.');
    }

    public function showPublic(string $slug): View
    {
        $page = LandingPage::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $page->increment('views_count');

        return view('crm.public-landing-page', ['page' => $page]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'campaign_id' => ['nullable', 'exists:crm_campaigns,id'],
            'headline' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', array_keys(LandingPage::STATUSES))],
        ]);
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'page';
        $slug = $base;
        $i = 1;
        while (LandingPage::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
