<?php

namespace App\Console\Commands;

use App\Http\Controllers\TrainingActivityController;
use App\Models\Training;
use App\Models\TrainingInterest;
use App\Notifications\TrainingClosedNotification;
use App\Notifications\TrainingInterestNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTrainingInterestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:traininginterest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send out notifications to users regarding their continued interest.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $trainings = Training::where([['status', '>=', 0], ['status', '<=', 1], ['created_at', '<=', Carbon::now()->subDays(30)]])->get();

        foreach ($trainings as $training) {
            $lastInterestRequest = TrainingInterest::where('training_id', $training->id)->orderBy('created_at')->get()->last();

            if ($lastInterestRequest == null) {
                // A notification has NOT been sent previously
                // Generate training interest key and store it in the request
                $key = sha1($training->id . now()->format('Ymd_His') . rand(0, 9999));

                $interest = TrainingInterest::create([
                    'training_id' => $training->id,
                    'key' => $key,
                    'deadline' => now()->addDays(14),
                ]);

                // Send notification to student
                $training->user->notify(new TrainingInterestNotification($training, $interest));
            } else {
                // A notification has been sent previously

                $requestDeadline = $lastInterestRequest->deadline;
                $requestConfirmed = $lastInterestRequest->confirmed_at;
                $requestUpdated = $lastInterestRequest->updated_at;

                if ($requestDeadline->diffInMinutes(now(), false) >= 0 && $requestConfirmed == null && $lastInterestRequest->expired == false) {
                    // If it's 14 days passed deadline, close the training
                    $this->info('Closing training ' . $training->id);

                    $oldStatus = $training->status;

                    // Update the training
                    // Note: The training interest is set to expire through updateStatus()
                    $training->updateStatus(-4, true);
                    $training->closed_reason = 'Continued training interest was not confirmed within deadline.';
                    $training->save();
                    $training->user->notify(new TrainingClosedNotification($training, -4, 'Continued training interest was not confirmed within deadline.'));
                    TrainingActivityController::create($training->id, 'STATUS', -4, $oldStatus, null, 'Continued training interest was not confirmed within deadline.');
                } elseif ($requestDeadline->diffInDays(now(), true) == 6 && $requestUpdated->diffInDays(now(), true) != 0 && $lastInterestRequest->expired == false && $requestConfirmed == null) {
                    // If the interest is not confirmed after 6 days, we remind
                    $this->info('Reminding training ' . $training->id);

                    $lastInterestRequest->updated_at = now();
                    $lastInterestRequest->save();

                    $training->user->notify(new TrainingInterestNotification($training, $lastInterestRequest, true));
                } elseif ($lastInterestRequest->created_at->diffInDays(now(), true) >= 30 && $lastInterestRequest->expired == true) {
                    // The training has been previously notified, after 30 days it's time for a new request
                    // Generate training interest key and store it in the request
                    $key = sha1($training->id . now()->format('Ymd_His') . rand(0, 9999));

                    $interest = TrainingInterest::create([
                        'training_id' => $training->id,
                        'key' => $key,
                        'deadline' => now()->addDays(14),
                    ]);

                    // Send notification to student
                    $training->user->notify(new TrainingInterestNotification($training, $interest));
                }
            }
        }

        $this->info('Training interests have been updated and followed up.');
    }
}
