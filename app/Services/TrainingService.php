<?php

namespace App\Services;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Helpers\TrainingStatus;
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
     * Run the completion work for a training: per-rating endorsements, then ATC activation.
     */
    public function completeTraining(Training $training): ?string
    {
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
     * endorsement; VATSIM ratings are a no-op.
     */
    public function completeRating(Training $training, Rating $rating): ?string
    {
        if ($rating->vatsim_rating != null) {
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

        return null;
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
