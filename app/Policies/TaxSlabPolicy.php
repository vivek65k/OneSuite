<?php

namespace App\Policies;

use App\Models\TaxSlab;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxSlabPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_tax_slab');
    }

    public function create(User $user): bool
    {
        return $user->can('create_tax_slab');
    }

    public function update(User $user, TaxSlab $taxSlab): bool
    {
        return $user->can('update_tax_slab');
    }

    public function delete(User $user, TaxSlab $taxSlab): bool
    {
        return $user->can('delete_tax_slab');
    }
}
