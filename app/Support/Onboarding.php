<?php

namespace App\Support;

use App\Models\Company;
use App\Models\OnboardingTask;
use App\Models\ProbationRecord;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shared onboarding helpers: per-category presentation (colour / icon),
 * tenant-scoped queries and the counts shown in the onboarding sub-nav.
 */
class Onboarding
{
    /** @var array<string, array{short: string, tone: string, icon: string}> */
    public const CATEGORY_META = [
        'welcome-checklist' => ['short' => 'Welcome', 'tone' => 'pink', 'icon' => 'ti-confetti'],
        'document-verification' => ['short' => 'Documents', 'tone' => 'sky', 'icon' => 'ti-file-check'],
        'asset-allocation' => ['short' => 'Assets', 'tone' => 'amber', 'icon' => 'ti-devices'],
        'it-account-creation' => ['short' => 'IT accounts', 'tone' => 'violet', 'icon' => 'ti-device-laptop'],
        'orientation' => ['short' => 'Orientation', 'tone' => 'teal', 'icon' => 'ti-compass'],
    ];

    /** @var array<string, array{tone: string, dot: string, icon: string}> */
    public const PROBATION_META = [
        'on_probation' => ['tone' => 'sky', 'dot' => 'info', 'icon' => 'ti-clock'],
        'extended' => ['tone' => 'amber', 'dot' => 'warning', 'icon' => 'ti-calendar-stats'],
        'confirmed' => ['tone' => 'success', 'dot' => 'success', 'icon' => 'ti-shield-check'],
        'terminated' => ['tone' => 'danger', 'dot' => 'danger', 'icon' => 'ti-circle-x'],
    ];

    public static function meta(string $category): array
    {
        return self::CATEGORY_META[$category]
            ?? ['short' => OnboardingTask::CATEGORIES[$category] ?? $category, 'tone' => 'slate', 'icon' => 'ti-list-check'];
    }

    /** Onboarding tasks of the current company (and branch). */
    public static function taskQuery(): Builder
    {
        return OnboardingTask::query()
            ->where('company_id', Company::current()->id)
            ->when(
                BranchContext::isRestricted(),
                fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId()))
            );
    }

    /** Probation records of the current company (and branch). */
    public static function probationQuery(): Builder
    {
        return ProbationRecord::query()
            ->where('company_id', Company::current()->id)
            ->when(
                BranchContext::isRestricted(),
                fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId()))
            );
    }

    /**
     * Users that onboarding tasks may be assigned to - only the current
     * company's own users (never other clients').
     */
    public static function assignableUsers(): Collection
    {
        return User::query()
            ->where('company_id', Company::current()->id)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Active probations (on probation / extended) whose review is due on or
     * before $by - including overdue ones. An extension moves the due date.
     */
    public static function reviewsDueQuery($by): Builder
    {
        $by = $by->toDateString();

        return self::probationQuery()->where(fn ($q) => $q
            ->where(fn ($q2) => $q2->where('status', 'on_probation')->whereDate('end_date', '<=', $by))
            ->orWhere(fn ($q2) => $q2->where('status', 'extended')
                ->whereRaw('COALESCE(extended_to, end_date) <= ?', [$by])));
    }

    /** Due date reference for "overdue": the review date, or the extension if extended. */
    public static function probationDueDate(ProbationRecord $record)
    {
        return $record->status === 'extended' && $record->extended_to ? $record->extended_to : $record->end_date;
    }

}
