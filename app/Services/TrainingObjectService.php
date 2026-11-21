<?php

namespace App\Services;

use App\Facades\DivisionApi;
use App\Helpers\LogName;
use App\Helpers\VatsimRating;
use App\Http\Controllers\FileController;
use App\Models\Position;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\TrainingObject;
use App\Models\TrainingObjectAttachment;
use App\Models\TrainingReport;
use App\Models\User;
use App\Notifications\MentorExaminationNotification;
use App\Notifications\TrainingExamNotification;
use App\Notifications\TrainingReportNotification;
use App\Services\DivisionApi\DivisionApiError;
use App\Tasks\Types\RatingUpgrade;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The service layer for training reports, examinations and their attachments.
 *
 * Shared by the web controllers and the management API.
 */
class TrainingObjectService
{
    /**
     * Create a training report, attach its files, and notify the student when it is published.
     *
     * @param  array{content: string, contentimprove?: string|null, report_date: CarbonInterface|string, position?: string|null, draft?: bool}  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function createReport(Training $training, array $data, ?int $authorId, array $files = []): TrainingReport
    {
        $report = TrainingReport::create([
            'training_id' => $training->id,
            'written_by_id' => $authorId,
            'content' => $data['content'],
            'contentimprove' => $data['contentimprove'] ?? null,
            'report_date' => $data['report_date'],
            'position' => $data['position'] ?? null,
            'draft' => (bool) ($data['draft'] ?? false),
        ]);

        $this->saveAttachments($files, $report, $authorId);

        // Notify student of new training request if it's not a draft
        if ($report->draft != true && $training->user->setting_notify_newreport) {
            $training->user->notify(new TrainingReportNotification($training, $report));
        }

        return $report;
    }

    /**
     * Update a training report, notifying the student when it is published for the first time.
     *
     * @param  array<string, mixed>  $data  report columns to change
     */
    public function updateReport(TrainingReport $report, array $data): TrainingReport
    {
        $report->update($data);

        // Notify student when the report is first published (published_at just stamped)
        if ($report->wasChanged('published_at') && $report->training->user->setting_notify_newreport) {
            $report->training->user->notify(new TrainingReportNotification($report->training, $report));
        }

        return $report;
    }

    /**
     * Create a training examination, sync it to the Division API when it applies, and notify.
     *
     * Files are uploaded to the Division API first for S2+ examinations, and a failure
     * there aborts before anything is saved. Optionally creates the rating upgrade task.
     *
     * Returns the Division API error string on failure, otherwise the examination.
     *
     * @param  array{position: string, result: string, examination_date: CarbonInterface, request_task_user_id?: int|string|null, subject_training_rating_id?: int|string|null}  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function createExamination(Training $training, array $data, ?int $examinerId, array $files = []): TrainingExamination|string
    {
        $files = Arr::flatten($files);
        $position = Position::firstWhere('callsign', $data['position']);
        $pass = strtolower($data['result']) == 'passed' ? true : false;

        // Attempt Division API sync first if the training has VATSIM ratings and it's an S2+ examination
        if ($files && $training->hasVatsimRatings() && $training->getHighestVatsimRating()->vatsim_rating->isGreaterThanOrEqual(VatsimRating::S2)) {
            foreach ($files as $file) {
                $response = DivisionApi::uploadExamResults($training->user->id, $examinerId, $pass, $position->callsign, $file->getRealPath());
                if ($response && $response->failed()) {
                    return 'Please try uploading the examination again. Failed to upload exam results to the Division API: ' . DivisionApiError::detail($response);
                }
            }
        }

        // Save locally
        $examination = TrainingExamination::create([
            'position_id' => $position->id,
            'training_id' => $training->id,
            'examiner_id' => $examinerId,
            'examination_date' => $data['examination_date']->format('Y-m-d'),
        ]);

        if (array_key_exists('result', $data)) {
            $examination->update(['result' => $data['result']]);
        }

        $attachmentId = $this->saveAttachments($files, $examination, $examinerId);
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
            $taskService = app(TaskService::class);

            if ($taskAsignee !== null && $taskService->canReceiveTasks($taskAsignee)) {
                $taskService->createTask([
                    'type' => RatingUpgrade::class,
                    'subject_user_id' => $training->user->id,
                    'subject_training_id' => $training->id,
                    'subject_training_rating_id' => $taskRating,
                    'assignee_user_id' => $taskAsignee->id,
                    'creator_user_id' => $examinerId,
                ]);
            }
        }

        return $examination;
    }

    /**
     * Update a training examination.
     *
     * @param  array<string, mixed>  $data  examination columns to change
     */
    public function updateExamination(TrainingExamination $examination, array $data): TrainingExamination
    {
        $examination->update($data);

        return $examination;
    }

    /**
     * Delete a training examination.
     */
    public function deleteExamination(TrainingExamination $examination): void
    {
        $examination->delete();

        ActivityLogService::danger(LogName::Training, 'Deleted training examination ' . $examination->id . ' ― From Training ' . $examination->training->id);
    }

    /**
     * Store the uploaded files and attach them to a report or examination.
     *
     * Returns the ids of every attachment the object now has.
     *
     * @param  array<int|string, UploadedFile|array<int|string, UploadedFile>>  $files
     * @return array<int, string>
     */
    public function saveAttachments(array $files, TrainingObject $object, ?int $uploadedBy = null): array
    {
        foreach ($files as $file) {
            if (! is_iterable($file)) {
                $this->attachFile($file, $object, $uploadedBy);
            } else {
                foreach ($file as $nestedFile) {
                    $this->attachFile($nestedFile, $object, $uploadedBy);
                }
            }
        }

        return $object->fresh()->attachments()->pluck('id')->toArray();
    }

    /**
     * Delete an attachment and its stored file.
     */
    public function deleteAttachment(TrainingObjectAttachment $attachment): void
    {
        Storage::delete($attachment->file->full_path);
        $attachment->delete();
    }

    /**
     * Store one uploaded file and attach it to the object.
     */
    private function attachFile(UploadedFile $file, TrainingObject $object, ?int $uploadedBy): void
    {
        $object->attachments()->create([
            'file_id' => FileController::saveFile($file, null, $uploadedBy),
            'hidden' => false, // We hardcode this to false for now
        ]);
    }
}
