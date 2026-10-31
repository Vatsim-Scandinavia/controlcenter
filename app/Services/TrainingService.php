<?php

namespace App\Services;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Helpers\TrainingStatus;
use App\Http\Controllers\TrainingActivityController;
use App\Models\AtcActivity;
use App\Models\Endorsement;
use App\Models\Rating;
use App\Models\Training;
use App\Models\User;
use App\Notifications\TrainingClosedNotification;
use App\Services\DivisionApi\DivisionApiError;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

/**
 * The service layer for acting on trainings.
 *
 * This is where training actions live so that controllers, console commands,
 * and member-sync code all drive the same behaviour instead of each
 * re-implementing it inline. New training actions belong here, not scattered
 * across callers. Keeping the logic in one place is what lets the rest of the
 * app touch different parts of a training's lifecycle without duplicating it.
 *
 * Today it owns the closed/completed transition. The rest of the lifecycle
 * still lives in the oversized TrainingController (~750 lines) and should be
 * pulled in here incrementally:
 *
 * @todo Route the hand-rolled closures through closeTraining() so the close +
 *       notification path is defined once. Duplicate sites: TrainingController::close(),
 *       and the UpdateMemberDetails, SendTrainingInterestNotifications, and UserDelete
 *       console commands. It is not a drop-in replacement for them yet, so do not
 *       swap them over blindly: closeTraining() reads $training->status without
 *       persisting it (those sites rely on updateStatus() for that), and it notifies
 *       with $training->closed_reason, which updateStatus() never sets, so the
 *       commands' hardcoded reason strings would silently vanish from the student's
 *       email. Give it an explicit reason argument and make it persist the status.
 * @todo Move mentor attach/detach syncing out of TrainingController::updateDetails.
 * @todo Move the paused-time accounting out of TrainingController::updateDetails.
 * @todo Move the store / apply / updateRequest domain logic behind this service.
 */
class TrainingService
{
    /**
     * Close a training, running the completion work when it is being completed.
     *
     * Precondition: $training->status is already set to the target closed status.
     * Returns the Division API error string on failure (no notification sent),
     * or null on success.
     */
    public function closeTraining(Training $training): ?string
    {
        $training->mentors()->detach();

        if ($training->status === TrainingStatus::COMPLETED) {
            $error = $this->completeTraining($training);
            if ($error !== null) {
                return $error;
            }
        }

        $training->user->notify(new TrainingClosedNotification($training, $training->status, $training->closed_reason));

        return null;
    }

    /**
     * Complete a single VATSIM rating part of a multi-rating training.
     *
     * Stamps the part, activates ATC for the training area, and logs it on the training
     * timeline. When it stamps the last outstanding part, the training is completed and
     * closed through closeTraining(), so this and the full-completion flow send exactly
     * one TrainingClosedNotification each.
     *
     * Facility ratings are never completable individually: they are granted by full
     * completion, which calls the Division API for the tier endorsement.
     *
     * Returns an error string for the caller to flash, or null on success.
     */
    public function completeRatingPart(Training $training, Rating $rating): ?string
    {
        if (! $training->status->isInProgress()) {
            return 'Only a training in progress can have a part completed.';
        }

        if ($training->ratings->count() < 2) {
            return 'A single-rating training is completed by setting its status to Completed.';
        }

        if (! $training->ratings->contains($rating->id)) {
            return $rating->name . ' is not part of this training.';
        }

        if ($rating->vatsim_rating === null) {
            return $rating->name . ' is granted when the whole training is completed, not part by part.';
        }

        if ($this->ratingPartIsCompleted($training, $rating)) {
            return 'The ' . $rating->name . ' part is already completed.';
        }

        $this->stampRatingCompleted($training, $rating);
        $this->activateAtcForTraining($training);

        // A RATING entry rather than a COMMENT: TrainingActivityPolicy hides COMMENT from
        // the student, and the timeline renders COMMENT with an author edit button. A part
        // sign-off is neither private nor editable, and holding the rating id keeps the
        // entry readable after a rating is renamed.
        TrainingActivityController::create($training->id, 'RATING', $rating->id, null, Auth::id());

        // Load-bearing: the outstanding check below reads the pivots we just wrote.
        $training->load('ratings');

        if ($training->outstandingRatings()->isNotEmpty()) {
            return null;
        }

        // Every part is done and all are VATSIM ratings, so finish the training.
        return $this->completeWholeTraining($training);
    }

    /**
     * Complete a whole training and close it.
     *
     * This is what finishes a training whose outstanding ratings cannot be signed off part
     * by part, which is every facility and tier rating. closeTraining() grants each of them
     * its endorsement, so a training can end with one endorsement or several.
     *
     * Returns an error string for the caller to flash, or null on success.
     */
    public function completeWholeTraining(Training $training): ?string
    {
        if (! $training->status->isInProgress()) {
            return 'Only a training in progress can be completed.';
        }

        $oldStatus = $training->status;
        $training->updateStatus(TrainingStatus::COMPLETED);
        TrainingActivityController::create($training->id, 'STATUS', TrainingStatus::COMPLETED->value, $oldStatus->value, Auth::id());

        return $this->closeTraining($training);
    }

    /**
     * Whether this rating part of the training has already been completed.
     */
    private function ratingPartIsCompleted(Training $training, Rating $rating): bool
    {
        return $training->ratings()
            ->wherePivotNotNull('completed_at')
            ->wherePivot('rating_id', $rating->id)
            ->exists();
    }

    /**
     * Run the completion work for a training: per-rating endorsements, then ATC activation.
     */
    public function completeTraining(Training $training): ?string
    {
        // Deliberately unfiltered: a VATSIM part stamped earlier by completeRatingPart()
        // re-runs completeRating() as a no-op (stampRatingCompleted() keeps the first
        // stamp), but a facility part must re-run so a reopened and re-completed
        // training still revokes the old endorsement and grants a fresh one.
        foreach ($training->ratings as $rating) {
            $error = $this->completeRating($training, $rating);
            if ($error !== null) {
                return $error;
            }
        }

        $this->activateAtcForTraining($training);

        return null;
    }

    /**
     * Apply one rating's completion effect. Facility ratings (vatsim_rating null)
     * revoke the prior endorsement, call the Division API, and grant a fresh
     * endorsement; VATSIM ratings only get stamped.
     */
    public function completeRating(Training $training, Rating $rating): ?string
    {
        if ($rating->vatsim_rating != null) {
            $this->stampRatingCompleted($training, $rating);

            return null;
        }

        // Revoke the old endorsement if active
        $oldEndorsement = $training->user->endorsements->where('type', 'FACILITY')->where('revoked', false)->where('expired', false);
        foreach ($oldEndorsement as $oe) {
            foreach ($oe->ratings as $oer) {
                if ($oer->id == $rating->id) {
                    $oe->revoked = true;
                    $oe->valid_to = now();
                    $oe->save();
                    break;
                }
            }
        }

        // All clear, let's start by attemping the insertion to the API
        $response = DivisionApi::assignTierEndorsement($training->user, $rating, Auth::id());
        if ($response && $response->failed()) {
            return DivisionApiError::message($response);
        }

        // Grant new endorsement
        $endorsement = new Endorsement();
        $endorsement->user_id = $training->user->id;
        $endorsement->type = 'FACILITY';
        $endorsement->valid_from = now()->format('Y-m-d H:i:s');
        $endorsement->valid_to = null;
        $endorsement->issued_by = null;
        $endorsement->save();

        $endorsement->ratings()->save(Rating::find($rating->id));

        // Stamped last, and only on success, so a failed call leaves the part outstanding
        // for a retry. The stamp records the grant; completeTraining() re-runs stamped
        // parts deliberately.
        $this->stampRatingCompleted($training, $rating);

        return null;
    }

    /**
     * Mark one rating part of the training as completed.
     *
     * Keeps the first stamp: a part is completed once, and re-running completion must
     * not move its date.
     */
    private function stampRatingCompleted(Training $training, Rating $rating): void
    {
        $outstanding = $training->ratings()
            ->wherePivotNull('completed_at')
            ->wherePivot('rating_id', $rating->id)
            ->exists();

        if ($outstanding) {
            $training->ratings()->updateExistingPivot($rating->id, ['completed_at' => now()]);
        }
    }

    /**
     * Set the user active for the training area, gated by the total-hours setting,
     * training type, and division membership.
     *
     * Repeat calls restart the activity grace period on purpose: finishing another
     * training earns a fresh window, even when the current one is still running.
     * The activation itself is idempotent.
     */
    public function activateAtcForTraining(Training $training): void
    {
        // atcActivityBasedOnTotalHours is false OR true and type is not familiarisation
        if (! Setting::get('atcActivityBasedOnTotalHours') || Setting::get('atcActivityBasedOnTotalHours') && $training->type <= 4) {

            // Apply activity only if user belongs to accepted divisions.
            // Visitors should manually be granted visitor endorsement, hence not covered here.
            if ($this->isUserInDivision($training->user)) {

                try {
                    $activity = AtcActivity::where('user_id', $training->user->id)->where('area_id', $training->area->id)->firstOrFail();
                    $activity->atc_active = true;
                    $activity->start_of_grace_period = now();
                    $activity->save();
                } catch (ModelNotFoundException $e) {
                    AtcActivity::create([
                        'user_id' => $training->user->id,
                        'area_id' => $training->area->id,
                        'hours' => 0,
                        'atc_active' => true,
                        'start_of_grace_period' => now(),
                    ]);
                }
            }
        }
    }

    public function isUserInDivision(User $user): bool
    {
        if (config('app.mode') === 'subdivision') {
            return in_array($user->subdivision, array_map('trim', explode(',', Setting::get('trainingSubDivisions'))));
        }

        return $user->division === config('app.owner_code');
    }
}
