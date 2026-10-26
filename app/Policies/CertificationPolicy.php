<?php

namespace App\Policies;

use App\Models\Certification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_certification');
    }

    public function create(User $user): bool
    {
        return $user->can('create_certification');
    }

    public function update(User $user, Certification $certification): bool
    {
        return $user->can('update_certification');
    }

    public function delete(User $user, Certification $certification): bool
    {
        return $user->can('delete_certification');
    }
}
