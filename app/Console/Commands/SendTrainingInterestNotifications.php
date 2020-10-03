<?php

namespace App\Console\Commands;

use App\Http\Controllers\TrainingController;
use App\Notifications\TrainingInterestNotification;
use App\Notifications\TrainingClosedNotification;
use App\Training;
use App\TrainingInterest;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
     * @return mixed
     */
    public function handle()
    {

        $trainings = Training::where([['started_at', null], ['created_at', '<=', Carbon::now()->subDays(30)], ['status', '=', 0]])->get();

        foreach ($trainings as $training) {

            $lastInterestRequest = TrainingInterest::where('training_id', $training->id)->orderBy('created_at')->get()->last();

            if($lastInterestRequest == null) {
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

                if ($requestDeadline->diffInDays(now()) <= 0 && $requestConfirmed == null) {
                    // If it's 14 days passed deadline, close the training
                    $this->info("Closing training ".$training->id);

                    $training->updateStatus(-4);
                    $training->user->notify(new TrainingClosedNotification($training, -4, 'Continued training interested was not confirmed within deadline.'));


                } elseif ($requestDeadline->diffInDays(now()) == 7 && $requestUpdated->diffInDays(now()) != 0 && $requestConfirmed == null) {
                    // If the interest is not confirmed after 7 days, we remind
                    $this->info("Reminding training ".$training->id);

                    $requestUpdated = now();
                    $lastInterestRequest->save();

                    $training->user->notify(new TrainingInterestNotification($training, $lastInterestRequest, true));     


                } elseif ($lastInterestRequest->created_at->diffInDays(now()) >= 30) {
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

        $this->info('Notifications have been sent');

        return;
    }
}
