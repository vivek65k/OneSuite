<?php

namespace App\Models\Concerns;

use App\Models\Scopes\EmployeeDataScope;
use App\Support\DataScope;

/**
 * For every model that holds one employee's data (leave, attendance,
 * payslips, documents, ... and Employee itself): its queries are limited
 * to, and its writes refused outside, the employees the current screen's
 * role may reach. See App\Support\DataScope for the whole picture.
 *
 * The employee is read from employee_id by default. Override
 * dataScopeColumn() when it lives elsewhere, or dataScopeVia() to name the
 * parent relation a child row inherits it from.
 */
trait ScopedByEmployee
{
    public static function bootScopedByEmployee(): void
    {
        static::addGlobalScope(new EmployeeDataScope());

        static::saving(fn (self $record) => DataScope::guardWrite($record));
        static::deleting(fn (self $record) => DataScope::guardWrite($record));
    }

    public function dataScopeColumn(): string
    {
        return 'employee_id';
    }

    public function dataScopeVia(): ?string
    {
        return null;
    }

    /**
     * The employee this record belongs to. A child row asks its parent,
     * loaded without the scope so the answer doesn't depend on who is asking.
     */
    public function dataScopeEmployeeId(): ?int
    {
        if ($relation = $this->dataScopeVia()) {
            return $this->{$relation}()->withoutGlobalScope(EmployeeDataScope::class)->first()?->dataScopeEmployeeId();
        }

        $employeeId = $this->getAttribute($this->dataScopeColumn());

        return $employeeId === null ? null : (int) $employeeId;
    }
}
