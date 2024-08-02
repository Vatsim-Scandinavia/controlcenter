<?php

namespace App\Http\Controllers;

use App\Facades\DivisionApi;
use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Models\Group;
use App\Models\OneTimeLink;
use App\Models\Position;
use App\Models\Task;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\User;
use App\Notifications\MentorExaminationNotification;
use App\Notifications\TrainingExamNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Controller for training examinations
 */
class TrainingExaminationController extends Controller
{
    /**
     * Show view to create an examination
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request, Training $training)
    {
        $this->authorize('create', [TrainingExamination::class, $training]);
        if ($training->status != TrainingStatus::AWAITING_EXAM->value) {
            return redirect(null, 400)->to($training->path())->withSuccess('Training examination cannot be created for a training not awaiting exam.');
        }

        $positions = Position::all();
        $taskRecipients = collect(Group::admins()->merge(Group::moderators()));
        $taskPopularAssignees = TaskController::getPopularAssignees($training->area);

        return view('training.exam.create', compact('training', 'positions', 'taskRecipients', 'taskPopularAssignees'));
    }

    /**
     * Store the examination in the database
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, Training $training)
    {
        $this->authorize('create', [TrainingExamination::class, $training]);
        $data = $this->validateRequest();
        $date = Carbon::createFromFormat('d/m/Y', $data['examination_date']);
        $position = Position::firstWhere('callsign', $data['position']);
        $pass = strtolower($data['result']) == 'passed' ? true : false;

        // Attempt Division API sync first if the training has VATSIM ratings and it's an S2+ examination
        if ($request->file('files') && $training->hasVatsimRatings() && $training->getHighestVatsimRating()->vatsim_rating >= VatsimRating::S2->value) {
            foreach ($request->file('files') as $file) {
                $response = DivisionApi::uploadExamResults($training->user->id, Auth::id(), $pass, $position->callsign, $file->getRealPath());
                if ($response && $response->failed()) {
                    return redirect()->back()->withErrors('Please try uploading the examination again. Failed to upload exam results to the Division API: ' . $response->json()['message']);
                }
            }
        }

        // Save locally
        $examination = TrainingExamination::create([
            'position_id' => $position->id,
            'training_id' => $training->id,
            'examiner_id' => Auth::id(),
            'examination_date' => $date->format('Y-m-d'),
        ]);

        if (array_key_exists('result', $data)) {
            $examination->update(['result' => $data['result']]);
        }

        $attachmentId = TrainingObjectAttachmentController::saveAttachments($request, $examination);
        $attachmentUrl = (isset($attachmentId[0])) ? route('training.object.attachment.show', ['attachment' => $attachmentId[0]]) : null;

        // Notify the training user
        $training->user->notify(new TrainingExamNotification($training, $examination));

        // Notify mentors if there are any
        if ($training->mentors->count() >= 1) {
            $training->user->notify(new MentorExaminationNotification($training->mentors, $training->user, $examination, $attachmentUrl, 'View examination report'));
        }

        // Create the upgrade task for the staff
        if (isset($data['request_task_user_id'])) {

            $taskAsignee = User::find($data['request_task_user_id']);
            $taskRating = isset($data['subject_training_rating_id']) ? (int) $data['subject_training_rating_id'] : null;
            if ($taskAsignee->can('receive', Task::class)) {
                $task = Task::create([
                    'type' => \App\Tasks\Types\RatingUpgrade::class,
                    'subject_user_id' => $training->user->id,
                    'subject_training_id' => $training->id,
                    'subject_training_rating_id' => $taskRating,
                    'assignee_user_id' => $taskAsignee->id,
                    'creator_user_id' => Auth::id(),
                    'created_at' => now(),
                ]);

                // Run the create method on the task type to trigger type specific actions on creation
                $task->type()->create($task);
            }

        }

        // Redirect based on if request was made by OTL or other means
        if (($key = session()->get('onetimekey')) != null) {
            // Remove the link
            OneTimeLink::where('key', $key)->delete();
            session()->pull('onetimekey');

            return redirect('dashboard')->withSuccess('Examination successfully added');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Examination successfully added',
                'id' => $examination->id,
            ]);
        }

        return redirect(route('training.show', $training->id))->withSuccess('Examination successfully added');
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, TrainingExamination $examination)
    {
        $this->authorize('update', $examination);

        $examination->update($this->validateRequest());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Examination successfully updated']);
        }

        return redirect()->back()->withSuccess('Examination successfully updated');
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, TrainingExamination $examination)
    {
        $this->authorize('delete', $examination);

        $examination->delete();
        ActivityLogController::danger('TRAINING', 'Deleted training examination ' . $examination->id . ' ― From Training ' . $examination->training->id);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Examination successfully deleted']);
        }

        return redirect()->back()->withSuccess('Examination successfully deleted');
    }

    private function validateRequest()
    {
        return request()->validate([
            'position' => 'required|exists:positions,callsign',
            'result' => ['required', Rule::in(['FAILED', 'PASSED', 'INCOMPLETE', 'POSTPONED'])],
            'examination_date' => 'sometimes|date_format:d/m/Y',
            'files.*' => 'sometimes|file|mimes:pdf|max:10240',
            'request_task_user_id' => 'nullable|exists:users,id',
            'subject_training_rating_id' => 'nullable|exists:ratings,id',
        ]);
    }
}
