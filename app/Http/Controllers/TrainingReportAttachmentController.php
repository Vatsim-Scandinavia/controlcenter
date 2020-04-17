<?php

namespace App\Http\Controllers;

use App\File;
use App\TrainingReport;
use App\TrainingReportAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrainingReportAttachmentController extends Controller
{

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param TrainingReport $report
     * @return false|string
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, TrainingReport $report)
    {
        $data = $request->validate([
            'file' => 'required|file',
            'hidden' => 'nullable'
        ]);

        $this->authorize('create', TrainingReportAttachment::class);
        $file = FileController::saveFile($request->file('file'));

        $attachment = TrainingReportAttachment::create([
            'training_report_id' => $report->id,
            'file_id' => $file,
            'hidden' => key_exists('hidden', $data) ? true : false
        ]);

        if ($request->expectsJson()) {
            return json_encode([
                'id' => $attachment->id,
                'message' => 'File successfully uploaded'
            ]);
        }

        return redirect()->back()->with('message', 'Attachment successfully addded');

    }

    /**
     * Display the specified resource.
     *
     * @param \App\TrainingReportAttachment $attachment
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(TrainingReportAttachment $attachment)
    {
        $this->authorize('view', $attachment);

        return redirect(route('file.get', ['file' => $attachment->file]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TrainingReportAttachment  $trainingReportAttachment
     * @return \Illuminate\Http\Response
     */
    public function edit(TrainingReportAttachment $trainingReportAttachment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\TrainingReportAttachment  $trainingReportAttachment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TrainingReportAttachment $trainingReportAttachment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param TrainingReportAttachment $attachment
     * @return false|string
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, TrainingReportAttachment $attachment)
    {
        $this->authorize('delete', $attachment);

        Storage::delete($attachment->file->full_path);
        $attachment->delete();

        if ($request->wantsJson()) {
            return json_encode(['message' => 'Attachment successfully deleted']);
        }

        return redirect()->back()->with('message', 'Attachment successfully deleted');
    }
}
