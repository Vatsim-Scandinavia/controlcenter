<?php

namespace App\Http\Controllers;

use App\Models\OneTimeLink;
use App\Models\Position;
use App\Models\Training;
use App\Models\TrainingReport;
use App\Notifications\TrainingReportNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for handling training reports
 */
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
        if ($training->status < 1) { return redirect(null, 400)->back()->withErrors('Training report cannot be created for a training not in progress.'); }

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

        if (isset($data['report_date']))
            $data['report_date'] = Carbon::createFromFormat('d/m/Y', $data['report_date'])->format('Y-m-d H:i:s');

        (isset($data['draft'])) ? $data['draft'] = true : $data['draft'] = false;

        $data2 = $data; // TODO this should be refactored to something better
        unset($data2['files']);
        $report = TrainingReport::create($data2);

        TrainingObjectAttachmentController::saveAttachments($request, $report);

        // Notify student of new training request if it's not a draft
        if($report->draft != true && $training->user->setting_notify_newreport){
            $training->user->notify(new TrainingReportNotification($training, $report));
        }

        if (($key = session()->get('onetimekey')) != null) {
            // Remove the link
            OneTimeLink::where('key', $key)->delete();
            session()->pull('onetimekey');

            return redirect('dashboard')->withSuccess('Report successfully created');
        }

        return redirect(route('training.show', $training->id))->withSuccess('Report successfully created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TrainingReport  $trainingReport
     * @return \Illuminate\Http\Response
     */
    public function show(TrainingReport $trainingReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param TrainingReport $report
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(TrainingReport $report)
    {
        $this->authorize('update', $report);

        $positions = Position::all();

        return view('training.report.edit', compact('report', 'positions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TrainingReport $report
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, TrainingReport $report)
    {
        $this->authorize('update', $report);
        $oldDraftStatus = $report->fresh()->draft;

        $data = $this->validateRequest();

        if (isset($data['report_date']))
            $data['report_date'] = Carbon::createFromFormat('d/m/Y', $data['report_date'])->format('Y-m-d H:i:s');

        (isset($data['draft'])) ? $data['draft'] = true : $data['draft'] = false;

        $report->update($data);

        // Notify student of new training request if it's not a draft anymore
        if($oldDraftStatus == false && $report->draft == true && $report->training->user->setting_notify_newreport){
            $report->training->user->notify(new TrainingReportNotification($report->training, $report));
        }

        return redirect()->back()->withSuccess('Training report successfully updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\TrainingReport $report
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
     * 
     * @return mixed
     */
    protected function validateRequest()
    {
        return request()->validate([
            'content' => 'sometimes|required',
            'contentimprove' => 'nullable',
            'report_date' => 'required|date_format:d/m/Y',
            'position' => 'nullable',
            'draft' => 'sometimes',
            'files.*' => 'sometimes|file|mimes:pdf,xls,xlsx,doc,docx,txt,png,jpg,jpeg',
            'contentimprove' => 'sometimes|nullable|string'
        ]);
    }
}
