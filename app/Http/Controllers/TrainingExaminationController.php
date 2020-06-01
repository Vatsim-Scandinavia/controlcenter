<?php

namespace App\Http\Controllers;

use DateTime;
use App\Position;
use App\Training;
use App\TrainingExamination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TrainingExaminationController extends Controller
{

    /**
     * Show the specified examination
     *
     * @param Request $request
     * @param TrainingExamination $examination
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, TrainingExamination $examination)
    {
        $this->authorize('view', $examination);
    }

    /**
     * Show view to create an examination
     *
     * @param Request $request
     * @param Training $training
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request, Training $training)
    {
        $this->authorize('createExamination', $training);
        
        $positions = Position::all();

        return view('trainingExam.create', compact('training', 'positions'));
    }

    /**
     * Store the examination in the database
     *
     * @param Request $request
     * @param Training $training
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, Training $training)
    {
        $this->authorize('createExamination', $training);

        $data = $this->validateRequest();
        $data = $request->validate([
            'date' => 'required|date:Y-m-d',
            'position' => 'required|exists:positions,callsign',
            'result' => ['required', Rule::in(['FAILED', 'PASSED', 'CANCELLED', 'POSTPONED'])],
        ]);

        $date = new DateTime($data['date']);
        $position_id = Position::all()->firstWhere('callsign', $data['position'])->id;
        
        $examination = TrainingExamination::create([
            'position_id' => $position_id,
            'training_id' => $training->id,
            'examiner_id' => Auth::id(),
            'examination_date' => $date,
        ]);

        if (key_exists('result', $data)) {
            $examination->update(['result' => $data['result']]);
        }

        if (key_exists('examination_sheet', $data)) {
            $id = FileController::saveFile($request->file('examination_sheet'), $request->file('examination_sheet')->getClientOriginalName());
            $examination->update(['examination_sheet' => $id]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Examination successfully added',
                'id' => $examination->id
            ]);
        }

        return redirect()->back()->with('message', 'Examination successfully added');

    }

    /**
     * @param Request $request
     * @param TrainingExamination $examination
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, TrainingExamination $examination)
    {
        $this->authorize('update', $examination);

        $examination->update($this->validateRequest());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Examination successfully updated']);
        }

        return redirect()->back()->with('message', 'Examination successfully updated');

    }

    /**
     * @param Request $request
     * @param TrainingExamination $examination
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, TrainingExamination $examination)
    {
        $this->authorize('delete', $examination);

        $examination->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Examination successfully deleted']);
        }

        return redirect()->back()->with('message', 'Examination successfully deleted');

    }

    private function validateRequest()
    {
        return request()->validate([
            'position_id' => 'sometimes|integer',
            'result' => 'sometimes|string',
            'examination_date' => 'sometimes|date',
            'examination_sheet' => 'sometimes|file'
        ]);
    }

}
