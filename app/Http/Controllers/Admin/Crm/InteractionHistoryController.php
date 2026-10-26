<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\Crm\ResolvesRelatable;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Crm\Note;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InteractionHistoryController extends Controller
{
    use ResolvesRelatable;

    /**
     * Every activity and note logged against one record (lead, account,
     * contact, or opportunity), merged into a single chronological feed -
     * the full story of that relationship in one place.
     */
    public function index(Request $request): View
    {
        $options = $this->relatableOptions();
        $selected = $request->query('relatable') ?: $options->keys()->first();

        $events = collect();
        $relatedType = null;
        $relatedId = null;

        if ($selected) {
            $relatedType = $this->relatedTypeFromKey($selected);
            $relatedId = $this->relatedIdFromKey($selected);

            if ($relatedType && $relatedId) {
                $activities = Activity::where('company_id', Company::current()->id)
                    ->where('related_type', $relatedType)->where('related_id', $relatedId)
                    ->with('owner')->get();

                $notes = Note::where('company_id', Company::current()->id)
                    ->where('notable_type', $relatedType)->where('notable_id', $relatedId)
                    ->with('owner')->get();

                foreach ($activities as $activity) {
                    $events->push([
                        'date' => $activity->completed_at ?? $activity->start_at ?? $activity->due_at ?? $activity->created_at,
                        'type' => $activity->type,
                        'subject' => $activity->subject,
                        'detail' => $activity->description,
                        'owner' => $activity->owner?->name,
                        'status' => $activity->status,
                    ]);
                }

                foreach ($notes as $note) {
                    $events->push([
                        'date' => $note->created_at,
                        'type' => 'note',
                        'subject' => 'Note',
                        'detail' => $note->body,
                        'owner' => $note->owner?->name,
                        'status' => null,
                    ]);
                }
            }
        }

        return view('admin.crm.interaction-history.index', [
            'options' => $options,
            'selected' => $selected,
            'label' => $relatedType && $relatedId ? $this->relatedLabel($relatedType, $relatedId) : null,
            'events' => $events->sortByDesc('date')->values(),
        ]);
    }
}
