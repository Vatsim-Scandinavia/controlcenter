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

    /**
     * Determine whether the user may grant or revoke the requested role
     * for the model user. A null area means a global (area-less) assignment.
     */
    public function updateRole(User $user, User $model, string $requestedRole, ?Area $requestedArea): bool
    {
        if (! $this->update($user, $model)) {
            return false;
        }

        // The admin role is managed exclusively through the user:makeadmin CLI command
        if ($requestedRole === 'admin') {
            return false;
        }

        // Global assignments require the role's scope to allow them
        if ($requestedArea === null && ! in_array(config("roles.roles.{$requestedRole}.scope"), ['both', 'global'], true)) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        // Only global directors may grant or revoke the director role
        if ($requestedRole === 'director') {
            return $user->hasGlobalRole('director');
        }

        // Global assignments of the remaining roles also require a global director
        if ($requestedArea === null) {
            return $user->hasGlobalRole('director');
        }

        // Directors manage the remaining roles within their scope
        if ($user->hasRole('director', $requestedArea)) {
            return true;
        }

        // Moderators can only set mentors and buddies
        if ($user->hasRole('moderator', $requestedArea)) {
            return in_array($requestedRole, ['mentor', 'buddy']);
        }

        return false;
    }
}
