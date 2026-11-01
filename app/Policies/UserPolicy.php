<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;
use App\Services\RoleSource;
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
        return $user->hasPermission('users.manage');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return bool
     */
    public function view(User $user, User $model)
    {
        return $user->is($model) || $user->hasPermission('users.manage') || $user->isTeaching($model);
    }

    /**
     * Determine whether the user can view the access table.
     *
     * @return bool
     */
    public function viewAccess(User $user)
    {
        return $user->hasPermission('users.access.view');
    }

    /**
     * Determine whether the user can view the reports of themselves or another user.
     *
     * @return bool
     */
    public function viewReports(User $user, User $model)
    {
        return $user->is($model) || $user->hasPermission('fir.management.reports.view');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return bool
     */
    public function update(User $user, User $model)
    {
        return $user->hasPermission('users.manage');
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

        $roleSource = app(RoleSource::class);
        $definition = $roleSource->role($requestedRole);

        // A role whose definition is no longer active (its module was disabled
        // or uninstalled) leaves dangling assignments. Those grant nothing while
        // the definition is absent, but a module manager must still be able to
        // revoke them. Authorise only when the target already holds this exact
        // assignment (so it can only be a revocation, never a new grant of an
        // undefined role) and the role is module-contributed.
        if ($definition === null) {
            $holdsAssignment = $model->roleAssignments()
                ->where('role', $requestedRole)
                ->when(
                    $requestedArea === null,
                    fn ($query) => $query->whereNull('area_id'),
                    fn ($query) => $query->where('area_id', $requestedArea->id),
                )
                ->exists();

            return $holdsAssignment
                && $roleSource->isModuleRole($requestedRole)
                && $user->hasGlobalPermission('system.modules.manage');
        }

        // Global (area-less) assignments require the role's scope to allow them
        if ($requestedArea === null
            && ! in_array($definition['scope'] ?? null, ['both', 'global'], true)) {
            return false;
        }

        // Module-contributed roles are governed by the module-management
        // permission rather than a roles.<role>.manage permission that nothing
        // registers.
        $permission = $roleSource->isModuleRole($requestedRole)
            ? 'system.modules.manage'
            : "roles.{$requestedRole}.manage";

        // Grant authority must be held at (or above) the grant's scope. A global grant always
        // needs global authority; a role declaring grant_scope 'global' needs it even in an area.
        $requiresGlobalAuthority = $requestedArea === null
            || ($definition['grant_scope'] ?? 'area') === 'global';

        return $requiresGlobalAuthority
            ? $user->hasGlobalPermission($permission)
            : $user->hasPermission($permission, $requestedArea);
    }
}
