<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_course');
    }

    public function create(User $user): bool
    {
        return $user->can('create_course');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->can('update_course');
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->can('delete_course');
    }
}
