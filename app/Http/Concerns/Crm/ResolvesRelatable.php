<?php

namespace App\Http\Concerns\Crm;

use App\Models\Company;
use App\Models\Crm\Activity;
use Illuminate\Support\Collection;

/**
 * Shared "what is this logged against" picker used by Tasks, Calls,
 * Meetings, Emails, Notes, and Interaction History - every activity-ish
 * record can point at a Lead, Account, Contact, or Opportunity, so the
 * dropdown-building and label-resolving logic lives here once.
 */
trait ResolvesRelatable
{
    protected function relatableOptions(): Collection
    {
        $companyId = Company::current()->id;
        $options = collect();

        foreach (Activity::RELATABLE as $key => $class) {
            $field = Activity::RELATABLE_LABEL_FIELDS[$key] ?? 'name';
            $options = $options->merge(
                $class::where('company_id', $companyId)->orderBy($field)->get(['id', $field])
                    ->mapWithKeys(fn ($record) => [$key.':'.$record->id => ucfirst(str_replace('_', ' ', $key)).' · '.$record->{$field}])
            );
        }

        return $options;
    }

    protected function relatedTypeFromKey(string $key): ?string
    {
        [$prefix] = explode(':', $key.':');

        return Activity::RELATABLE[$prefix] ?? null;
    }

    protected function relatedIdFromKey(string $key): ?int
    {
        $parts = explode(':', $key);

        return isset($parts[1]) ? (int) $parts[1] : null;
    }

    protected function relatableValue(?string $type, ?int $id): ?string
    {
        if (! $type || ! $id) {
            return null;
        }

        $key = array_search($type, Activity::RELATABLE, true);

        return $key ? $key.':'.$id : null;
    }

    protected function relatedLabel(?string $type, ?int $id): ?string
    {
        if (! $type || ! $id) {
            return null;
        }

        $record = $type::find($id);
        if (! $record) {
            return null;
        }

        $key = array_search($type, Activity::RELATABLE, true);
        $field = Activity::RELATABLE_LABEL_FIELDS[$key] ?? 'name';

        return $record->{$field};
    }
}
