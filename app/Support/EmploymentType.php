<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Employment types are free-text columns (employees, job_positions). The
 * forms save "Full-time" while older/seeded rows hold "full_time", so
 * everything here compares on a normalized slug to treat both as one type.
 */
class EmploymentType
{
    /** Values the create/edit forms offer and save. */
    public const OPTIONS = ['Full-time', 'Part-time', 'Contract', 'Intern'];

    public static function slug(?string $type): string
    {
        return Str::slug((string) $type, '_');
    }

    public static function label(?string $type): string
    {
        return Str::headline((string) $type);
    }

    /**
     * Form options plus the matching one for $current, so editing a record
     * stored as "full_time" preselects "Full-time" instead of blanking it.
     * An unknown legacy value is appended so it's never silently dropped.
     *
     * @return array{options: list<string>, selected: ?string}
     */
    public static function formOptions(?string $current): array
    {
        $options = self::OPTIONS;
        $selected = collect($options)->first(fn ($type) => self::slug($type) === self::slug($current));

        if (filled($current) && ! $selected) {
            $options[] = $selected = $current;
        }

        return ['options' => $options, 'selected' => $selected];
    }

    /**
     * Types actually in use on $query's table, keyed by slug.
     *
     * @return array<string, array{label: string, values: list<string>}>
     */
    public static function inUse(Builder $query, string $column = 'employment_type'): array
    {
        return $query
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->pluck($column)
            ->groupBy(fn ($type) => self::slug($type))
            ->map(fn ($values) => ['label' => self::label($values->first()), 'values' => $values->values()->all()])
            ->sortBy('label')
            ->all();
    }
}
