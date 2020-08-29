<?php

namespace App\Http\Controllers;

use App\Position;
use App\Training;
use App\TrainingReport;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Training $training
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Training $training)
    {
        $this->authorize('viewReports', $training);

        $reports = Auth::user()->viewableModels(TrainingReport::class, [['training_id', '=', $training->id]]);

        return view('training.report.index', compact('training', 'reports'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Training $training
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Training $training)
    {
        $this->authorize('createReport', $training);
        if ($training->status != 1) { return redirect(null, 400)->back()->withSuccess('Training report cannot be created for a training not in progress.'); }

        $positions = Position::all();

        return view('training.report.create', compact('training', 'positions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Training $training
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function store(Request $request, Training $training)
    {
        $this->authorize('create', TrainingReport::class);

        $data = $this->validateRequest();
        $data['written_by_id'] = Auth::id();
        $data['training_id'] = $training->id;

        $data2 = $data; // TODO this should be refactored to something better
        unset($data2['files']);
        $report = TrainingReport::create($data2);

        TrainingReportAttachmentController::saveAttachments($request, $report);

        return redirect(route('training.show', $training->id))->withSuccess('Report successfully created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\TrainingReport  $trainingReport
     * @return \Illuminate\Http\Response
     */
    public function show(TrainingReport $trainingReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TrainingReport  $trainingReport
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(TrainingReport $report)
    {
        $positions = Position::all();
        return view('training.report.edit', compact('report', 'positions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\TrainingReport $report
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, TrainingReport $report)
    {
        $this->authorize('update', $report);

        $report->update($this->validateRequest());

        return redirect()->back()->withSuccess('Training report successfully updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\TrainingReport $report
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(TrainingReport $report)
    {
        $this->authorize('delete', $report);

        $report->delete();

        return redirect(route('training.show', $report->training->id))->withSuccess('Training report deleted');
    }

    /**
     * Validates the request data
     */
    protected function validateRequest()
    {
        return request()->validate([
            'content' => 'sometimes|required',
            'mentor_notes' => 'nullable',
            'position' => 'nullable',
            'draft' => 'sometimes|required|boolean',
            'files.*' => 'sometimes|file'
        ]);
    }
}
