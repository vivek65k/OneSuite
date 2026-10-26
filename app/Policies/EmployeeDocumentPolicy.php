<?php

namespace App\Policies;

use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeDocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_employee_document');
    }

    public function create(User $user): bool
    {
        return $user->can('create_employee_document');
    }

    public function update(User $user, EmployeeDocument $employeeDocument): bool
    {
        return $user->can('update_employee_document');
    }

    public function delete(User $user, EmployeeDocument $employeeDocument): bool
    {
        return $user->can('delete_employee_document');
    }
}
