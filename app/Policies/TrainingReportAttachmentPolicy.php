<?php

namespace App\Policies;

use App\TrainingReportAttachment;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingReportAttachmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the training report attachment.
     *
     * @param  \App\User  $user
     * @param  \App\TrainingReportAttachment  $attachment
     * @return mixed
     */
    public function view(User $user, TrainingReportAttachment $attachment)
    {
        return ($user->can('view', $attachment->report) && $attachment->hidden != true) || $user->isMentor();
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
     * Determine whether the user can update the training report attachment.
     *
     * @param  \App\User  $user
     * @param  \App\TrainingReportAttachment  $trainingReportAttachment
     * @return mixed
     */
    public function update(User $user, TrainingReportAttachment $trainingReportAttachment)
    {
        //
    }

    /**
     * Determine whether the user can delete the training report attachment.
     *
     * @param  \App\User  $user
     * @param  \App\TrainingReportAttachment  $trainingReportAttachment
     * @return mixed
     */
    public function delete(User $user, TrainingReportAttachment $trainingReportAttachment)
    {
        //
    }

    /**
     * Determine whether the user can restore the training report attachment.
     *
     * @param  \App\User  $user
     * @param  \App\TrainingReportAttachment  $trainingReportAttachment
     * @return mixed
     */
    public function restore(User $user, TrainingReportAttachment $trainingReportAttachment)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the training report attachment.
     *
     * @param  \App\User  $user
     * @param  \App\TrainingReportAttachment  $trainingReportAttachment
     * @return mixed
     */
    public function forceDelete(User $user, TrainingReportAttachment $trainingReportAttachment)
    {
        //
    }
}
