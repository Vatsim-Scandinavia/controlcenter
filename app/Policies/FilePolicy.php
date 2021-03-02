<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the file.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return bool
     */
    public function view(User $user, File $file)
    {
        return  $user->isModeratorOrAbove() ||
                $user->is($file->owner) ||
                ($file->trainingReportAttachment != null ? $user->can('view', $file->trainingReportAttachment) : false);
    }

    /**
     * Determine whether the user can create files.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isMentorOrAbove();
    }

    /**
     * Determine whether the user can update the file.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return bool
     */
    public function update(User $user, File $file)
    {
        return $user->isModeratorOrAbove() || $user->is($file->owner);
    }

    /**
     * Determine whether the user can delete the file.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return bool
     */
    public function delete(User $user, File $file)
    {
        return $user->isModeratorOrAbove() || $user->is($file->owner);
    }
}
