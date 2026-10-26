<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\Crm\ResolvesRelatable;
use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class NoteController extends Controller
{
    use ResolvesCurrentEmployee, ResolvesRelatable;

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));

        $query = Note::where('company_id', $companyId)->with('owner');

        if ($search !== '') {
            $query->where('body', 'like', "%{$search}%");
        }

        $notes = $query->latest()->paginate(25)->withQueryString();

        return view('admin.crm.notes.index', [
            'notes' => $notes->through(fn ($n) => tap($n, fn ($x) => $x->relatedLabel = $this->relatedLabel($x->notable_type, $x->notable_id))),
            'search' => $search,
            'total' => Note::where('company_id', $companyId)->count(),
            'withAttachment' => Note::where('company_id', $companyId)->whereNotNull('file_path')->count(),
            'relatable' => $this->relatableOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'relatable' => ['required', 'string'],
            'body' => ['required', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $relatedType = $this->relatedTypeFromKey($data['relatable']);
        $relatedId = $this->relatedIdFromKey($data['relatable']);
        abort_unless($relatedType && $relatedId && $relatedType::where('company_id', $companyId)->where('id', $relatedId)->exists(), 404);

        $note = Note::create([
            'company_id' => $companyId,
            'notable_type' => $relatedType,
            'notable_id' => $relatedId,
            'body' => $data['body'],
            'owner_employee_id' => optional(auth()->user()->employee)->id,
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('crm-notes', 'public');
            $note->update(['file_path' => $path, 'file_name' => $request->file('file')->getClientOriginalName()]);
        }

        return back()->with('status', 'Note added.');
    }

    public function destroy(Note $note): RedirectResponse
    {
        abort_unless($note->company_id === Company::current()->id, 404);

        if ($note->file_path) {
            Storage::disk('public')->delete($note->file_path);
        }

        $note->delete();

        return back()->with('status', 'Note removed.');
    }
}
