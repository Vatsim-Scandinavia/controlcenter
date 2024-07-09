<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingActivity;
use Illuminate\Http\Request;

class TrainingActivityController extends Controller
{
    /*
    * A table over allowed activity types
    */
    public static $activityTypes = ['STATUS' => true, 'TYPE' => true, 'MENTOR' => true, 'PAUSE' => true, 'ENDORSEMENT' => true, 'COMMENT' => true, 'PRETRAINING' => true];

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function create(int $trainingId, string $type, ?int $new_data = null, ?int $old_data = null, ?int $userId = null, ?string $comment = null)
    {

        // Check is this type is accepted
        try {
            TrainingActivityController::$activityTypes[$type];
        } catch (\Exception $e) {
            throw new \App\Exceptions\InvalidTrainingActivityType('The type ' . $type . ' is not supported.');
        }

        $activity = new TrainingActivity();
        $activity->training_id = $trainingId;
        $activity->type = $type;
        $activity->new_data = $new_data;
        $activity->old_data = $old_data;
        $activity->triggered_by_id = $userId;
        $activity->comment = $comment;
        $activity->save();

        return $activity;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeComment(Request $request)
    {
        $data = request()->validate([
            'training_id' => 'required|exists:App\Models\Training,id',
            'comment' => 'required|string|max:512',
            'update_id' => 'nullable',
        ]);

        $this->authorize('comment', [TrainingActivity::class, Training::find($data['training_id'])]);

        // Check if it's a comment update
        if (isset($data['update_id'])) {
            $activity = TrainingActivity::find($data['update_id']);
            if ($activity == null) {
                return back()->withInput()->withErrors('Could not find comment to update.');
            }

            if ($activity->triggered_by_id != \Auth::user()->id) {
                return back()->withInput()->withErrors('You are not allowed to update this comment.');
            }

            $activity->comment = $data['comment'];
            $activity->save();

            return redirect()->back()->withSuccess('Comment updated.');
        }

        $activity = new TrainingActivity();
        $activity->training_id = $data['training_id'];
        $activity->triggered_by_id = \Auth::user()->id;
        $activity->type = 'COMMENT';
        $activity->comment = $data['comment'];
        $activity->save();

        return redirect()->back()->withSuccess('Comment added.');
    }
}
