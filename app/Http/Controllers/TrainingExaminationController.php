<?php

namespace App\Http\Controllers;

use App\Models\OneTimeLink;
use App\Models\Position;
use App\Models\Training;
use App\Models\TrainingExamination;
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
        if ($training->status != 3) {
            return redirect(null, 400)->to($training->path())->withSuccess('Training examination cannot be created for a training not awaiting exam.');
        }

        $positions = Position::all();

        return view('training.exam.create', compact('training', 'positions'));
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

        $position_id = Position::all()->firstWhere('callsign', $data['position'])->id;

        $examination = TrainingExamination::create([
            'position_id' => $position_id,
            'training_id' => $training->id,
            'examiner_id' => Auth::id(),
            'examination_date' => $date->format('Y-m-d'),
        ]);

        if (array_key_exists('result', $data)) {
            $examination->update(['result' => $data['result']]);
        }

        TrainingObjectAttachmentController::saveAttachments($request, $examination);

        $training->user->notify(new TrainingExamNotification($training, $examination));

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
            'files.*' => 'sometimes|file|mimes:pdf',
        ]);
    }
}
