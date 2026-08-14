<?php

namespace App\Jobs;

use App\Models\MoodleCourseRule;
use App\Models\MoodleEnrolment;
use App\Models\MoodleUserLink;
use App\Models\Training;
use App\Services\MoodleClient;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncMoodleTrainingEnrolments implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Training $training)
    {
        $this->afterCommit()->onConnection('database')->onQueue('moodle');
    }

    /**
     * Execute the job.
     */
    public function handle(MoodleClient $moodle): void
    {
        if (! $moodle->isConfigured()
            || ! Schema::hasTable('moodle_enrolments')) {
            return;
        }

        $training = $this->training->fresh(['user.moodleUserLink', 'ratings']);
        if ($training === null) {
            return;
        }

        if (Schema::hasTable('moodle_course_rules')) {
            MoodleCourseRule::query()
                ->where('area_id', $training->area_id)
                ->whereIn('rating_id', $training->ratings->pluck('id'))
                ->whereHas('course', fn ($query) => $query->where('enabled', true))
                ->each(fn (MoodleCourseRule $rule): MoodleEnrolment => MoodleEnrolment::firstOrCreate([
                    'training_id' => $training->id,
                    'moodle_course_id' => $rule->moodle_course_id,
                ]));
        }

        $enrolments = $training->moodleEnrolments()
            ->with('course')
            ->whereHas('course', fn ($query) => $query->where('enabled', true))
            ->get();

        if ($enrolments->isEmpty()) {
            return;
        }

        $userLink = $training->user->moodleUserLink ?? $this->matchUserByCid($moodle, $training);
        if ($userLink === null) {
            $enrolments->each->update([
                'status' => 'failed',
                'last_error' => "No Moodle user has username {$training->user_id}.",
            ]);

            return;
        }

        $lastException = null;
        foreach ($enrolments as $enrolment) {
            if ($enrolment->status === 'enrolled') {
                continue;
            }

            $enrolment->update([
                'moodle_user_link_id' => $userLink->id,
                'status' => 'pending',
                'attempts' => $enrolment->attempts + 1,
                'last_error' => null,
            ]);

            try {
                $moodle->enrolUser($userLink->moodle_user_id, $enrolment->course->moodle_id);
                $enrolment->update([
                    'status' => 'enrolled',
                    'last_error' => null,
                    'enrolled_at' => now(),
                ]);
            } catch (Throwable $exception) {
                $lastException = $exception;
                $enrolment->update([
                    'status' => 'failed',
                    'last_error' => $exception->getMessage(),
                ]);
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function uniqueId(): string
    {
        return (string) $this->training->getKey();
    }

    protected function matchUserByCid(MoodleClient $moodle, Training $training): ?MoodleUserLink
    {
        $cid = (string) $training->user_id;
        $matches = collect($moodle->findUsersByUsername($cid))
            ->filter(fn (array $user): bool => (string) ($user['username'] ?? '') === $cid)
            ->filter(fn (array $user): bool => ! ($user['deleted'] ?? false) && ! ($user['suspended'] ?? false));

        if ($matches->count() !== 1) {
            return null;
        }

        $match = $matches->first();

        return MoodleUserLink::updateOrCreate(
            ['user_id' => $training->user_id],
            [
                'moodle_user_id' => (int) $match['id'],
                'moodle_username' => (string) $match['username'],
                'moodle_full_name' => (string) ($match['fullname'] ?? trim(($match['firstname'] ?? '') . ' ' . ($match['lastname'] ?? ''))),
                'match_type' => 'automatic',
                'linked_by' => null,
                'verified_at' => now(),
            ]
        );
    }
}
