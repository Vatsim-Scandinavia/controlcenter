<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the file.
     *
     * @return bool
     */
    public function view(User $user, File $file)
    {
        return $user->hasPermission('files.manage') ||
                $user->is($file->owner) ||
                ($file->trainingReportAttachment != null ? $user->can('view', $file->trainingReportAttachment) : false);
    }

    /**
     * Determine whether the user can create files.
     *
     * @return bool
     */
    public function create(User $user)
    {
        return $user->hasPermission('files.upload');
    }

    /**
     * Determine whether the user can update the file.
     *
     * @return bool
     */
    public function update(User $user, File $file)
    {
        return $user->hasPermission('files.manage') || $user->is($file->owner);
    }

    /**
     * Determine whether the user can delete the file.
     *
     * @return bool
     */
    public function delete(User $user, File $file)
    {
        return $user->hasPermission('files.manage') || $user->is($file->owner);
    }
}
