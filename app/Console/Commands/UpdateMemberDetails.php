<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Http\Controllers\ActivityLogController;
use App\Models\AtcActivity;
use App\Models\Training;
use App\Models\User;
use App\Notifications\TrainingClosedNotification;
use Illuminate\Console\Command;

class UpdateMemberDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:member:details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Function updates members data depeninding to user\' VATSIM data.';

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
        $mentors = User::allWithGroup('3');

        if(config('app.mode') == 'subdivision'){
            $divisions = array_map('trim', explode(',', Setting::get('trainingSubDivisions')));
        } else {
            $divisions = array(config('app.owner_code'));
        }

        $this->info('Detaching mentors who no longer are in subdivision...');
        $count = 0;

        // Start the loop
        foreach ($mentors as $mentor) {
            if (config('app.mode') == 'subdivision' && in_array($mentor->subdivision, $divisions) || $mentor->isVisiting()) {
                continue;
            } else if(config('app.mode') == 'division' && $mentor->division == config('app.owner_code')) {
                continue;
            }

            // Remove any active trainings and training roles
            $mentor->teaches()->detach();

            // Remove mentor permission groups
            $mentor->groups()->detach();
            $mentor->save();

            $count++;
        }

        $this->info($count . ' users affected.');

        // Get active trainings
        $trainings = Training::where('status', '>=', 0)->where('type', '!=', 5)->get();

        $this->info('Closing trainings for those who left division...');
        $count = 0;

        foreach ($trainings as $training) {
            if (config('app.mode') == 'subdivision' && in_array($training->user->subdivision, $divisions)) {
                continue;
            } else if(config('app.mode') == 'division' && $training->user->division == config('app.owner_code')) {
                continue;
            }

            // Close the training
            $training->updateStatus(-4);
            $training->closed_reason = 'The student has left or is no longer part of our division.';
            $training->save();

            // Notify student of closure
            $training->user->notify(new TrainingClosedNotification($training, -4, 'Student has left the division.'));

            // Log the closure
            ActivityLogController::warning('TRAINING', 'Closed training request ' . $training->id . ' due to student leaving division');

            $count++;
        }

        $this->info($count . ' trainings affected.');

        // Make users who left division inactive
        $this->info('Making users who left division inactive...');
        $count = 0;

        $membersNotInDivision = User::whereNotIn(config('app.mode'), $divisions)->get();
        $activeMembersNotInDivision = User::getActiveAtcMembers($membersNotInDivision->pluck('id')->toArray());

        $activeMembersNotInDivision->each(function ($member) use (&$count) {

            // Set as ATC Inactive
            $atcActivitiesToSetAsInactive = $member->atcActivity->where('atc_active', true);
            AtcActivity::whereIn('id', $atcActivitiesToSetAsInactive->pluck('id'))->update(['atc_active' => false]);

            $count++;
        });

        $this->info($count . ' users affected.');

        $this->info('Done');
    }
}
