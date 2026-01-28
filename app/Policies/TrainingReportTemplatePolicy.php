<?php

namespace App\Policies;

use App\Models\TrainingReportTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingReportTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any templates.
     */
    public function viewAny(User $user): bool
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can view the template.
     */
    public function view(User $user, TrainingReportTemplate $template): bool
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can create templates.
     */
    public function create(User $user): bool
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can update the template.
     */
    public function update(User $user, TrainingReportTemplate $template): bool
    {
        return $user->isModeratorOrAbove();
    }

    /**
     * Determine whether the user can delete the template.
     */
    public function delete(User $user, TrainingReportTemplate $template): bool
    {
        return $user->isModeratorOrAbove();
    }
}

