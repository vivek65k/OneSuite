<?php

namespace App\Models\Scopes;

use App\Support\DataScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits an employee-linked model to the employees the current screen may
 * see (App\Support\DataScope). It does nothing at all unless a controller
 * has entered a screen whose permission has "team" or "own" access - so
 * "all" roles, the self-service screens, console commands and queued jobs
 * run exactly the queries they always did.
 */
class EmployeeDataScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $employeeIds = DataScope::activeEmployeeIds();

        if ($employeeIds === null) {
            return;
        }

        // A child row (a payslip line, a booking on a trip) is visible when
        // its parent is - and the parent's own copy of this scope does the
        // employee check inside the whereHas().
        if ($relation = $model->dataScopeVia()) {
            $builder->whereHas($relation);

            return;
        }

        $builder->whereIn($model->qualifyColumn($model->dataScopeColumn()), $employeeIds);
    }
}
