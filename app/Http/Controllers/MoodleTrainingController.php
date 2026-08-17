<?php

namespace App\Http\Controllers;

use App\Helpers\TrainingStatus;
use App\Jobs\SyncMoodleTrainingEnrolments;
use App\Models\MoodleEnrolment;
use App\Models\MoodleUserLink;
use App\Models\Training;
use App\Services\MoodleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class MoodleTrainingController extends Controller
{
    public function assignCourses(Request $request, Training $training): RedirectResponse
    {
        $this->authorize('update', $training);
        $this->ensurePreTraining($training);
        $validated = $request->validate([
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('moodle_courses', 'id')->where('enabled', true),
            ],
        ]);

        foreach ($validated['course_ids'] as $courseId) {
            $enrolment = MoodleEnrolment::firstOrNew([
                'training_id' => $training->id,
                'moodle_course_id' => $courseId,
            ]);

            if (! $enrolment->exists || $enrolment->status !== 'enrolled') {
                $enrolment->fill([
                    'status' => 'pending',
                    'last_error' => null,
                    'enrolled_at' => null,
                ])->save();
            }
        }

        SyncMoodleTrainingEnrolments::dispatch($training);

        return back()->withSuccess('Selected Moodle courses assigned and enrolment queued.');
    }

    public function searchUsers(Request $request, Training $training, MoodleClient $moodle): JsonResponse
    {
        $this->authorize('update', $training);
        $this->ensurePreTraining($training);
        $validated = $request->validate(['query' => ['required', 'string', 'min:2', 'max:100']]);

        try {
            return response()->json(['users' => $moodle->searchUsers($validated['query'])]);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function linkUser(Request $request, Training $training, MoodleClient $moodle): RedirectResponse
    {
        $this->authorize('update', $training);
        $this->ensurePreTraining($training);
        $validated = $request->validate(['moodle_user_id' => ['required', 'integer', 'min:1']]);

        try {
            $moodleUser = $moodle->findUserById((int) $validated['moodle_user_id']);
            if ($moodleUser === null
                || (int) ($moodleUser['id'] ?? 0) !== (int) $validated['moodle_user_id']
                || ($moodleUser['deleted'] ?? false)
                || ($moodleUser['suspended'] ?? false)) {
                return back()->withErrors('The selected Moodle user is not available.');
            }

            $conflictingLink = MoodleUserLink::query()
                ->where('moodle_user_id', $moodleUser['id'])
                ->where('user_id', '!=', $training->user_id)
                ->exists();
            if ($conflictingLink) {
                return back()->withErrors('That Moodle user is already linked to another Control Center user.');
            }

            $userLink = MoodleUserLink::updateOrCreate(
                ['user_id' => $training->user_id],
                [
                    'moodle_user_id' => (int) $moodleUser['id'],
                    'moodle_username' => $moodleUser['username'] ?? null,
                    'moodle_full_name' => (string) ($moodleUser['fullname'] ?? trim(($moodleUser['firstname'] ?? '') . ' ' . ($moodleUser['lastname'] ?? ''))),
                    'match_type' => 'manual',
                    'linked_by' => $request->user()->id,
                    'verified_at' => now(),
                ]
            );

            // A replacement identity must receive every configured course, including
            // courses that may already have succeeded for the previous identity.
            $training->moodleEnrolments()->update([
                'moodle_user_link_id' => $userLink->id,
                'status' => 'pending',
                'last_error' => null,
                'enrolled_at' => null,
            ]);
        } catch (Throwable $exception) {
            return back()->withErrors('Could not link the Moodle user: ' . $exception->getMessage());
        }

        SyncMoodleTrainingEnrolments::dispatch($training);

        return back()->withSuccess('Moodle user linked and enrolment queued.');
    }

    public function retry(Request $request, Training $training): RedirectResponse
    {
        $this->authorize('update', $training);
        $this->ensurePreTraining($training);

        SyncMoodleTrainingEnrolments::dispatch($training);

        return back()->withSuccess('Moodle enrolment queued for retry.');
    }

    protected function ensurePreTraining(Training $training): void
    {
        abort_unless($training->status === TrainingStatus::PRE_TRAINING, 422, 'Moodle enrolment is only available during pre-training.');
    }
}
