<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return bool
     */
    public function index(User $user)
    {
        return $user->hasPermission('manage-users');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return bool
     */
    public function view(User $user, User $model)
    {
        return $user->is($model) || $user->hasPermission('manage-users') || $user->isTeaching($model);
    }

    /**
     * Determine whether the user can view the access table.
     *
     * @return bool
     */
    public function viewAccess(User $user)
    {
        return $user->hasPermission('view-user-access');
    }

    /**
     * Determine whether the user can view the reports of themselves or another user.
     *
     * @return bool
     */
    public function viewReports(User $user, User $model)
    {
        return $user->is($model) || $user->hasPermission('view-management-reports');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return bool
     */
    public function update(User $user, User $model)
    {
        return $user->hasPermission('manage-users');
    }

    public function updateRole(User $user, User $model, string $requestedRole, Area $requestedArea)
    {
        if (! $this->update($user, $model)) {
            return false;
        }

        // Admins can set any role except admin (handled in controller)
        if ($user->hasRole('admin')) {
            return true;
        }

        // Moderators can only set mentors and buddies
        if ($user->hasRole('moderator', $requestedArea)) {
            return in_array($requestedRole, ['mentor', 'buddy']);
        }

        return false;
    }
}
