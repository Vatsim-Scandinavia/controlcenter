<?php

namespace App\Console\Commands;

use App\Mail\TrainingInterestMail;
use App\Notifications\TrainingInterestNotification;
use App\Notifications\TrainingClosedNotification;
use App\Training;
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
    protected $signature = 'send:interest-notifications';

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

        $trainings = Training::where([['started_at', null], ['created_at', '<=', now()->subtract('d', 30)], ['status', '=', 0]])->get();

        foreach ($trainings as $training) {

            $last = DB::table(Training::CONTINUED_INTEREST_NOTIFICATION_LOG_TABLE)
                ->where('training_id', $training->id)
                ->get()
                ->sortBy('created_at')
                ->last();

            // A notification hasn't been sent previously
            if ($last == null) {
                if ($training->created_at->diffInDays(now()) >= 30) {
                    $key = sha1($training->id . now()->format('Ymd_His') . rand(0, 9999));
                    $training->user->notify(new TrainingInterestNotification($training, $key));
                }
            }

            // A notification has been sent previously
            if ($last != null) {

                $created_at = Carbon::make($last->created_at);

                if ($created_at->diffInDays(now()) >= 14 && $last->confirmed_at == null) {
                    // Training should be closed
                    $training->updateStatus(-4);

                    // Notify the student
                    $training->user->notify(new TrainingClosedNotification($training, -4, 'Continued training interested was not confirmed within deadline.'));

                } elseif ($created_at->diffInDays(now()) >= 30 && $training->created_at->diffInDays(now()) >= 30) {
                    // Send new notification
                    $key = sha1($training->id . now()->format('Ymd_His') . rand(0, 9999));
                    $training->user->notify(new TrainingInterestNotification($training, $key));

                } elseif ($created_at->diffInDays(now()) >= 7) {
                    // Reminder should be sent
                    $key = $last->key;
                    $deadline = $last->deadline;
                    Mail::to($training->user)->send(new TrainingInterestMail($training, $key, $deadline));
                }

            }

        }

        $this->info('Notifications have been sent');

        return;
    }
}
