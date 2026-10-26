<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\KnowledgeArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $categoryFilter = (string) $request->query('category', '');

        $query = KnowledgeArticle::where('company_id', $companyId);

        if ($categoryFilter !== '') {
            $query->where('category', $categoryFilter);
        }
        if ($search !== '') {
            $query->where('title', 'like', "%{$search}%");
        }

        $articles = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.crm.knowledge-base.index', [
            'articles' => $articles,
            'search' => $search,
            'categoryFilter' => $categoryFilter,
            'categories' => KnowledgeArticle::where('company_id', $companyId)->whereNotNull('category')->distinct()->pluck('category'),
            'publishedCount' => KnowledgeArticle::where('company_id', $companyId)->where('is_published', true)->count(),
            'totalViews' => KnowledgeArticle::where('company_id', $companyId)->sum('views_count'),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.knowledge-base.form', ['article' => new KnowledgeArticle]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);

        $article = KnowledgeArticle::create($data + ['company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.knowledge-base.edit', $article)->with('status', 'Article created.');
    }

    public function edit(KnowledgeArticle $knowledgeArticle): View
    {
        abort_unless($knowledgeArticle->company_id === Company::current()->id, 404);

        return view('admin.crm.knowledge-base.form', ['article' => $knowledgeArticle]);
    }

    public function update(Request $request, KnowledgeArticle $knowledgeArticle): RedirectResponse
    {
        abort_unless($knowledgeArticle->company_id === Company::current()->id, 404);
        $knowledgeArticle->update($this->validated($request));

        return redirect()->route('admin.crm.knowledge-base.edit', $knowledgeArticle)->with('status', 'Article updated.');
    }

    public function destroy(KnowledgeArticle $knowledgeArticle): RedirectResponse
    {
        abort_unless($knowledgeArticle->company_id === Company::current()->id, 404);
        $knowledgeArticle->delete();

        return redirect()->route('admin.crm.knowledge-base.index')->with('status', 'Article deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'body' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
        ]) + ['is_published' => $request->boolean('is_published')];
    }

    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $i = 1;
        while (KnowledgeArticle::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
