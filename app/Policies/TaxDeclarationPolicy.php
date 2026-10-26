<?php

namespace App\Policies;

use App\Models\TaxDeclaration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxDeclarationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_tax_declaration');
    }

    public function create(User $user): bool
    {
        return $user->can('create_tax_declaration');
    }

    public function update(User $user, TaxDeclaration $taxDeclaration): bool
    {
        return $user->can('update_tax_declaration');
    }

    public function delete(User $user, TaxDeclaration $taxDeclaration): bool
    {
        return $user->can('delete_tax_declaration');
    }
}
