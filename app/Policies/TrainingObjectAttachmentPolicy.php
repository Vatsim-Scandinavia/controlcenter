<?php

namespace App\Policies;

use App\TrainingObjectAttachment;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingObjectAttachmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the training report attachment.
     *
     * @param  \App\User  $user
     * @param  \App\TrainingObjectAttachment  $attachment
     * @return mixed
     */
    public function view(User $user, TrainingObjectAttachment $attachment)
    {
        return ($user->can('view', $attachment->object) && $attachment->hidden != true) || $user->isMentor();
    }

    /**
     * Determine whether the user can create training report attachments.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isMentor();
    }

    /**
     * Determine whether the user can destroy training object attachments.
     *
     * @param User $user
     * @param TrainingObjectAttachment $attachment
     */
    public function delete(User $user, TrainingObjectAttachment $attachment)
    {
        return $user->isModerator() || $user->is($attachment->file->owner);
    }
}
