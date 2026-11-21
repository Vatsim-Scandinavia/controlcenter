<?php

namespace App\Services;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Helpers\LogName;
use App\Helpers\TrainingStatus;
use App\Http\Controllers\TrainingActivityController;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\Endorsement;
use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingActivity;
use App\Models\User;
use App\Notifications\TrainingClosedNotification;
use App\Notifications\TrainingCreatedNotification;
use App\Notifications\TrainingMentorNotification;
use App\Notifications\TrainingPreStatusNotification;
use App\Services\DivisionApi\DivisionApiError;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
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
 * It owns the creation, detail updates (status, mentors, pause accounting),
 * rating changes, pre-training toggle, comments and the closed/completed
 * transitions. Methods that record who acted take an optional actor id and
 * fall back to the signed-in user, so the web UI and the management API share
 * them.
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
 * @todo Move the apply / updateRequest eligibility checks behind this service.
 */
class TrainingService
{
    /**
     * Create a training request and send the confirmation to the student.
     *
     * Eligibility checks (open-training limit, hour requirements, refresh coverage)
     * stay with the caller, because they differ between an application and a staff
     * creation.
     *
     * @param  Collection<int, Rating>  $ratings
     */
    public function createTraining(
        User $student,
        int $areaId,
        int $type,
        Collection $ratings,
        ?int $createdById,
        string $motivation = '',
        ?int $experience = null,
        bool $englishOnly = false,
        ?string $applicationComment = null,
    ): Training {
        $training = Training::create([
            'user_id' => $student->id,
            'created_by' => $createdById,
            'area_id' => $areaId,
            'motivation' => $motivation,
            'experience' => $experience,
            'english_only_training' => $englishOnly,
            'type' => $type,
        ]);

        if ($applicationComment !== null) {
            TrainingActivityController::create($training->id, 'COMMENT', null, null, null, 'Comment from application: ' . $applicationComment);
        }

        $training->ratings()->saveMany($ratings);

        ActivityLogService::info(LogName::Training, 'Created training request ' . $training->id . ' for CID ' . $training->user_id . ' ― Ratings: ' . $ratings->pluck('name') . ' in ' . Area::find($training->area_id)->name);

        $training->user->notify(new TrainingCreatedNotification($training));

        return $training;
    }

    /**
     * The facility ratings a refresh or familiarisation training must still cover.
     *
     * A refresh has to renew every active endorsement the student holds in the area.
     * Returns the names of the endorsements missing from the requested ratings, empty
     * when the request is complete.
     *
     * @param  Collection<int, string>  $requestedRatings  rating names
     * @return Collection<int, string>
     */
    public function missingRefreshRatings(int $areaId, int $userId, Collection $requestedRatings): Collection
    {
        // Ratings applicable to area of request
        $areaRatings = Rating::whereHas('areas', function ($query) use ($areaId) {
            $query->where('area_id', $areaId);
        })->whereNull('vatsim_rating')->get()->pluck('name');

        // Ratings which user has today and needs to be refresh
        $userRatings = User::find($userId)->endorsements->where('type', 'FACILITY')->where('expired', false)->where('revoked', false)->map(function ($endorsement) {
            return $endorsement->ratings->first()->name;
        });

        // Gather expected ratings for this user in given area
        $expectedRatings = collect();
        foreach ($userRatings as $userRating) {
            if ($areaRatings->contains($userRating)) {
                $expectedRatings->push($userRating);
            }
        }

        return $expectedRatings->diff($requestedRatings);
    }

    /**
     * Replace the ratings of a training, keeping the completion stamps of the parts
     * that survive the change.
     *
     * @param  Collection<int, Rating>  $ratings
     */
    public function replaceRatings(Training $training, Collection $ratings): void
    {
        $previousRatings = $training->ratings;

        // Detach all ratings connected to training to save the new (or same) ones.
        $training->ratings()->detach();
        $training->ratings()->saveMany($ratings);

        // The detach above dropped the per-rating completion stamps. Put them back for the
        // ratings that survived the edit: a part that was signed off stays signed off.
        $completedAt = $previousRatings
            ->mapWithKeys(fn (Rating $rating) => [$rating->id => $rating->pivot->completed_at])
            ->filter();

        foreach ($ratings as $rating) {
            if ($completedAt->has($rating->id)) {
                $training->ratings()->updateExistingPivot($rating->id, ['completed_at' => $completedAt->get($rating->id)]);
            }
        }
    }

    /**
     * Apply a set of detail changes to a training: status, closed reason, mentors,
     * pause accounting, and any plain attributes.
     *
     * Every key of $changes is optional; null or absent means "leave untouched":
     *  - status: the new TrainingStatus
     *  - closed_reason: persisted with the training, and recorded on staff closures
     *  - mentors: the complete mentor list as user id => expire_at (null for the default
     *    12 months on newly attached mentors); mentors not listed are detached
     *  - paused: whether the training should be paused
     *  - attributes: any other training columns to persist in the same save
     *
     * Domain refusals (a closed training reopened over another open one) change nothing
     * and report `refused`. A Division API failure while closing is reported as `error`
     * after the rest has been saved, as the web flow always did.
     *
     * @param  array{status?: TrainingStatus|null, closed_reason?: string|null, mentors?: array<int, CarbonInterface|null>|null, paused?: bool|null, attributes?: array<string, mixed>}  $changes
     * @return array{refused: bool, error: string|null, closed: bool, preTraining: bool, mentorNotified: bool}
     */
    public function updateDetails(Training $training, array $changes, ?int $actorId = null): array
    {
        $actorId = $this->resolveActorId($actorId);
        $oldStatus = $training->fresh()->status;
        $attributes = $changes['attributes'] ?? [];
        $result = ['refused' => false, 'error' => null, 'closed' => false, 'preTraining' => false, 'mentorNotified' => false];

        if (array_key_exists('closed_reason', $changes)) {
            $attributes['closed_reason'] = $changes['closed_reason'];
        }

        $newStatus = $changes['status'] ?? null;
        if ($newStatus !== null) {

            // Don't allow re-opening a training if that causes student to have multiple trainings at the same time
            if ($newStatus->isOpen() && $oldStatus->isClosed() && $training->user->hasActiveTrainings(true)) {
                $result['refused'] = true;
                $result['error'] = 'Training can not be reopened. The student already has an active training request.';

                return $result;
            }

            // Stage the status change (and its derived timestamps) in memory so
            // the rest of this request reads the new state. Everything is then
            // persisted together in the single update below, keeping the
            // activity log to one entry per request.
            $training->fill($training->resolveStatusChanges($newStatus));

            if ($newStatus !== $oldStatus) {
                if ($newStatus === TrainingStatus::CLOSED_BY_STAFF || $newStatus === TrainingStatus::CLOSED_BY_SYSTEM) {
                    TrainingActivityController::create($training->id, 'STATUS', $newStatus->value, $oldStatus->value, $actorId, $changes['closed_reason'] ?? null);
                } else {
                    TrainingActivityController::create($training->id, 'STATUS', $newStatus->value, $oldStatus->value, $actorId);
                }
            }
        }

        if (isset($changes['mentors'])) {
            $result['mentorNotified'] = $this->syncMentors($training, $changes['mentors'], $actorId);
        }

        // Update paused time for queue estimation
        if (isset($changes['paused'])) {
            if ($changes['paused'] && $training->status->isOpen()) {
                if (! isset($training->paused_at)) {
                    $attributes['paused_at'] = Carbon::now();
                    TrainingActivityController::create($training->id, 'PAUSE', 1, null, $actorId);
                } else {
                    $attributes['paused_at'] = $training->paused_at;
                }
            } else {
                // If paused is unchecked but training is paused, sum up the length and unpause.
                if (isset($training->paused_at)) {
                    $attributes['paused_length'] = $training->paused_length + (int) Carbon::create($training->paused_at)->diffInSeconds(Carbon::now(), true);
                    TrainingActivityController::create($training->id, 'PAUSE', 0, null, $actorId);
                }

                $attributes['paused_at'] = null;
            }
        }

        // If training is closed, force to unpause
        if ($training->status !== $oldStatus) {
            if ($training->status->isClosed()) {
                $attributes['paused_at'] = null;
                if (isset($training->paused_at)) {
                    TrainingActivityController::create($training->id, 'PAUSE', 0, null, $actorId);
                }
            }
        }

        // Update the training
        $training->update($attributes);

        ActivityLogService::warning(LogName::Training, 'Updated training details ' . $training->id .
        ' ― Old Status: ' . $oldStatus->label() .
        ' ― New Status: ' . $training->status->label() .
        ' ― Mentor: ' . $training->mentors->pluck('name'));

        // Send e-mail and store endorsements rating (non-GRP ones), if it's a new status and it goes from active to closed
        if ($training->status !== $oldStatus) {
            if ($training->status->isClosed()) {
                $result['closed'] = true;
                $result['error'] = $this->closeTraining($training, $actorId);

                return $result;
            }

            if ($training->status === TrainingStatus::PRE_TRAINING) {
                $result['preTraining'] = true;
                $training->user->notify(new TrainingPreStatusNotification($training));
            }
        }

        return $result;
    }

    /**
     * Make the mentors of a training match the given list.
     *
     * Mentors who lack the mentor permission in the training area are skipped. New
     * mentors are attached with the given expiry (12 months when null), and an expiry
     * given for a mentor already attached moves it. Every attach and detach lands on
     * the training timeline.
     *
     * Returns whether the student was notified of a new mentor.
     *
     * @param  array<int, CarbonInterface|null>  $mentors  user id => expire_at
     */
    public function syncMentors(Training $training, array $mentors, ?int $actorId = null): bool
    {
        $actorId = $this->resolveActorId($actorId);
        $notifyOfNewMentor = false;

        foreach ($mentors as $mentorId => $expireAt) {
            $mentorUser = User::find($mentorId);

            if (! $training->mentors->contains($mentorId)) {
                if ($mentorUser != null && $mentorUser->hasPermission('training.mentor', $training->area)) {
                    $training->mentors()->attach($mentorId, ['expire_at' => $expireAt ?? now()->addMonths(12)]);

                    // Notify student of their new mentor
                    $notifyOfNewMentor = true;

                    TrainingActivityController::create($training->id, 'MENTOR', $mentorId, null, $actorId);
                }
            } elseif ($expireAt !== null) {
                $training->mentors()->updateExistingPivot($mentorId, ['expire_at' => $expireAt]);
            }
        }

        foreach ($training->mentors as $mentor) {
            if (! array_key_exists($mentor->id, $mentors)) {
                $training->mentors()->detach($mentor);
                TrainingActivityController::create($training->id, 'MENTOR', null, $mentor->id, $actorId);
            }
        }

        // Notify student of their new mentor. We put this here so detached mentors ain't included.
        if ($notifyOfNewMentor) {
            $training->user->notify(new TrainingMentorNotification($training));
        }

        return $notifyOfNewMentor;
    }

    /**
     * Flip the pre-training completed flag, or set it explicitly when $completed is given.
     *
     * Returns the resulting state. Setting a flag to the value it already holds changes
     * and logs nothing.
     */
    public function togglePreTrainingCompleted(Training $training, ?int $actorId = null, ?bool $completed = null): bool
    {
        $oldState = (bool) $training->pre_training_completed;
        $newState = $completed ?? ! $oldState;

        if ($newState === $oldState) {
            return $newState;
        }

        $training->pre_training_completed = $newState;
        $training->save();

        ActivityLogService::warning(LogName::Training, 'Student marked pre-training as completed ' . $training->id);
        TrainingActivityController::create($training->id, 'PRETRAINING', $newState, $oldState, $this->resolveActorId($actorId));

        return $newState;
    }

    /**
     * Add a free-text comment to the training timeline.
     */
    public function addComment(Training $training, string $comment, ?int $actorId = null): TrainingActivity
    {
        return TrainingActivityController::create($training->id, 'COMMENT', null, null, $this->resolveActorId($actorId), $comment);
    }

    /**
     * Close a training, running the completion work when it is being completed.
     *
     * Precondition: $training->status is already set to the target closed status.
     * Returns the Division API error string on failure (no notification sent),
     * or null on success.
     */
    public function closeTraining(Training $training, ?int $actorId = null): ?string
    {
        $training->mentors()->detach();

        if ($training->status === TrainingStatus::COMPLETED) {
            $error = $this->completeTraining($training, $actorId);
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
    public function completeRatingPart(Training $training, Rating $rating, ?int $actorId = null): ?string
    {
        $actorId = $this->resolveActorId($actorId);

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
        TrainingActivityController::create($training->id, 'RATING', $rating->id, null, $actorId);

        // Load-bearing: the outstanding check below reads the pivots we just wrote.
        $training->load('ratings');

        if ($training->outstandingRatings()->isNotEmpty()) {
            return null;
        }

        // Every part is done and all are VATSIM ratings, so finish the training.
        return $this->completeWholeTraining($training, $actorId);
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
    public function completeWholeTraining(Training $training, ?int $actorId = null): ?string
    {
        $actorId = $this->resolveActorId($actorId);

        if (! $training->status->isInProgress()) {
            return 'Only a training in progress can be completed.';
        }

        $oldStatus = $training->status;
        $training->updateStatus(TrainingStatus::COMPLETED);
        TrainingActivityController::create($training->id, 'STATUS', TrainingStatus::COMPLETED->value, $oldStatus->value, $actorId);

        return $this->closeTraining($training, $actorId);
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
    public function completeTraining(Training $training, ?int $actorId = null): ?string
    {
        // Deliberately unfiltered: a VATSIM part stamped earlier by completeRatingPart()
        // re-runs completeRating() as a no-op (stampRatingCompleted() keeps the first
        // stamp), but a facility part must re-run so a reopened and re-completed
        // training still revokes the old endorsement and grants a fresh one.
        foreach ($training->ratings as $rating) {
            $error = $this->completeRating($training, $rating, $actorId);
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
    public function completeRating(Training $training, Rating $rating, ?int $actorId = null): ?string
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
        $response = DivisionApi::assignTierEndorsement($training->user, $rating, $this->resolveActorId($actorId));
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

    /**
     * The id of whoever is acting: the given actor, otherwise the signed-in user.
     */
    private function resolveActorId(?int $actorId): ?int
    {
        return $actorId ?? Auth::id();
    }
}
