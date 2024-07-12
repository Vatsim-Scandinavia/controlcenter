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
     * @return bool
     */
    public function view(User $user, TrainingObjectAttachment $attachment)
    {
        $attachmentArea = $attachment->object->training->area;

        return ($user->can('view', $attachment->object) && $attachment->hidden != true) || $user->isMentor($attachmentArea);
    }

    /**
     * Determine whether the user can create training report attachments.
     *
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isMentorOrAbove();
    }

    /**
     * Determine whether the user can destroy training object attachments.
     *
     * @return bool
     */
    public function delete(User $user, TrainingObjectAttachment $attachment)
    {
        return $user->isModeratorOrAbove($attachment->object->training->area) || $user->is($attachment->file->owner);
    }
}
