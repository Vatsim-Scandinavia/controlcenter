<?php

namespace App\Http\Controllers;

use App\File;
use App\TrainingReport;
use App\TrainingReportAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use function MongoDB\BSON\toJSON;

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

        $attachment_ids = self::saveAttachments($request, $report);

        if ($request->expectsJson()) {
            return json_encode([
                'id' => $attachment_ids,
                'message' => 'File(s) successfully uploaded'
            ]);
        }

        return redirect()->back()->withSuccess('Attachment successfully addded');

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

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Attachment successfully deleted']);
        }

        return redirect()->back()->withSuccess('Attachment successfully deleted');
    }

    /**
     * @param Request $request
     * @param TrainingReport $report
     * @return array
     */
    public static function saveAttachments(Request $request, TrainingReport $report)
    {
        $attachment_ids = array();

        foreach ($request->allFiles() as $file) {
            $file_id = FileController::saveFile($file);

            $attachment = TrainingReportAttachment::create([
                'training_report_id' => $report->id,
                'file_id' => $file_id,
                'hidden' => false // We hardcode this to false for now
            ]);

            $attachment_ids[] = $attachment->id;
        }

        return $attachment_ids;
    }
}
