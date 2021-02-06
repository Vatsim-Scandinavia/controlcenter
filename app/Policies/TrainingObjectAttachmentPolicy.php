<?php

namespace App\Policies;

use App\Models\TrainingObjectAttachment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingObjectAttachmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the training report attachment.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TrainingObjectAttachment  $attachment
     * @return bool
     */
    public function view(User $user, TrainingObjectAttachment $attachment)
    {
        return ($user->can('view', $attachment->object) && $attachment->hidden != true) || $user->isMentor();
    }

    /**
     * Determine whether the user can create training report attachments.
     *
     * @param  \App\Models\User  $user
     * @return bool
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
     * @return bool
     */
    public function delete(User $user, TrainingObjectAttachment $attachment)
    {
        return $user->isModerator($attachment->object->training->country) || $user->is($attachment->file->owner);
    }
}
