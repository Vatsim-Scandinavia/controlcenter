<?php

namespace App\Http\Controllers;

use App\Helpers\TrainingStatus;
use App\Models\OneTimeLink;
use App\Models\Position;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\User;
use App\Services\TrainingObjectService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controller for training examinations
 */
class TrainingExaminationController extends Controller
{
    /**
     * Show view to create an examination
     *
     * @return Factory|View
     *
     * @throws AuthorizationException
     */
    public function create(Request $request, Training $training)
    {
        $this->authorize('create', [TrainingExamination::class, $training]);
        if ($training->status !== TrainingStatus::AWAITING_EXAM) {
            return redirect(null, 400)->to($training->path())->withSuccess('Training examination cannot be created for a training not awaiting exam.');
        }

        $positions = Position::all();
        $taskRecipients = collect(User::whereHas('roleAssignments', function ($q) {
            $q->whereIn('role', ['admin', 'moderator']);
        })->get());
        $taskPopularAssignees = TaskController::getPopularAssignees($training->area);

        return view('training.exam.create', compact('training', 'positions', 'taskRecipients', 'taskPopularAssignees'));
    }

    /**
     * Store the examination in the database
     *
     * @return JsonResponse|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function store(Request $request, Training $training)
    {
        $this->authorize('create', [TrainingExamination::class, $training]);
        $data = $this->validateRequest();
        $data['examination_date'] = Carbon::createFromFormat('d/m/Y', $data['examination_date']);

        $result = app(TrainingObjectService::class)->createExamination($training, $data, Auth::id(), $request->files->all());
        if (is_string($result)) {
            return redirect()->back()->withErrors($result);
        }

        $examination = $result;

        // If OTL was used, remove the link and session variable
        if (($key = OneTimeLink::getFromSession()) != null) {
            // Remove the link
            $key->delete();
            session()->pull('onetimekey');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Examination successfully added',
                'id' => $examination->id,
            ]);
        }

        $redirectTarget = route('dashboard');
        if ($request->user()->can('view', $training)) {
            $redirectTarget = route('training.show', $training->id);
        }

        return redirect($redirectTarget)->withSuccess('Examination successfully added');
    }

    /**
     * @return JsonResponse|RedirectResponse
     *
     * @throws AuthorizationException
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
     * @return JsonResponse|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function destroy(Request $request, TrainingExamination $examination)
    {
        $this->authorize('delete', $examination);

        app(TrainingObjectService::class)->deleteExamination($examination);

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
