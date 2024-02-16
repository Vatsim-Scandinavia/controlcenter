<?php

namespace App\Console\Commands;

use App\Models\Training;
use App\Models\User;
use App\Notifications\TrainingClosedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UserDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe or pseudonymize a user';

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
     * Close trainings of user to remove them from queue if applicable
     */
    private function closeUserTrainings($user)
    {

        $trainings = Training::where('user_id', $user->id)->where('status', '>=', 0)->get();
        foreach ($trainings as $training) {
            // Training should be closed
            $training->updateStatus(-4);

            // Detach mentors
            foreach ($training->mentors as $mentor) {
                $training->mentors()->detach($mentor);
            }

            // Notify the student
            $training->closed_reason = 'Closed due to data deletion request.';
            $training->save();
            $training->user->notify(new TrainingClosedNotification($training, -4, 'Closed due to data deletion request.'));
        }
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $cid = $this->ask("What is the user's CID?");

        if ($user = User::find($cid)) {
            $userInfo = $user->name . ' (' . $cid . ')';

            $this->comment($userInfo . ' found in records of Control Center!');

            $choices = [
                'PSEUDONYMISE -> Used for GDPR deletion requests from user directly to us',
                'PERMANENTLY DELETE -> Deletes all data related to this user, only applicable if user is banned from VATSIM and will never return',
            ];
            $choice = $this->choice('Do you want to PSEUDONYMISE or DELETE the user?', $choices);

            // PSEUDONYMISE
            if ($choice == $choices[0]) {
                $confirmed = $this->confirm('Are you sure you want to PSEUDONYMISE ' . $userInfo . '?');
                if ($confirmed) {
                    // Remove things from Control Center
                    $this->closeUserTrainings($user);
                    $user->groups()->detach();

                    $user->email = 'void@void.void';
                    $user->first_name = 'Anonymous';
                    $user->last_name = 'Anonymous';
                    $user->region = 'XXX';
                    $user->division = null;
                    $user->subdivision = null;
                    $user->access_token = null;
                    $user->refresh_token = null;
                    $user->token_expires = null;
                    $user->remember_token = null;
                    $user->setting_workmail_address = null;
                    $user->setting_workmail_expire = null;
                    $user->setting_notify_newreport = false;
                    $user->setting_notify_newreq = false;
                    $user->setting_notify_closedreq = false;
                    $user->setting_notify_newexamreport = false;

                    $user->save();

                    $this->comment($userInfo . ' has been pseudoymised in Control Center. This will be reverted IF they log into CC again.');
                }
                // PERMANENTLY DELETE
            } elseif ($choice == $choices[1]) {
                $confirmed = $this->confirm('Are you sure you want to PERMANENTLY DELETE ' . $userInfo . '? This is IRREVERSIBLE!');
                if ($confirmed) {
                    // Remove notification logs as it's not cascaded due to morph data structure
                    DB::table('notifications')->where('notifiable_type', 'App\Models\User')->where('notifiable_id', $cid)->delete();

                    // Remove things from Control Center
                    $user->delete();

                    $this->comment('All data related to ' . $userInfo . ' has been permanently deleted from Control Center!');
                }
            }
        } else {
            $this->error('No records of ' . $cid . ' was found.');
        }
    }
}
