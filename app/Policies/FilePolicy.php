<?php

namespace App\Policies;

use App\File;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the file.
     *
     * @param  \App\User  $user
     * @param  \App\File  $file
     * @return bool
     */
    public function view(User $user, File $file)
    {
        return  $user->isModerator() ||
                $user->is($file->owner) ||
                ($file->trainingReportAttachment != null ? $user->can('view', $file->trainingReportAttachment) : false);
    }

    /**
     * Determine whether the user can create files.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isMentor();
    }

    /**
     * Determine whether the user can update the file.
     *
     * @param  \App\User  $user
     * @param  \App\File  $file
     * @return bool
     */
    public function update(User $user, File $file)
    {
        return $user->isModerator() || $user->is($file->owner);
    }

    /**
     * Determine whether the user can delete the file.
     *
     * @param  \App\User  $user
     * @param  \App\File  $file
     * @return bool
     */
    public function delete(User $user, File $file)
    {
        return $user->isModerator() || $user->is($file->owner);
    }
}
