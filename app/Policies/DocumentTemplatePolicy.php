<?php

namespace App\Policies;

use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_document_template');
    }

    public function create(User $user): bool
    {
        return $user->can('create_document_template');
    }

    public function update(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can('update_document_template');
    }

    public function delete(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can('delete_document_template');
    }
}
