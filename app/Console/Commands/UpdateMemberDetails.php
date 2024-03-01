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

        $subdivisions = array_map('trim', explode(',', Setting::get('trainingSubDivisions')));

        $this->info('Detaching mentors who no longer are in subdivision...');
        $count = 0;

        // Start the loop
        foreach ($mentors as $mentor) {
            if (in_array($mentor->subdivision, $subdivisions) || $mentor->isVisiting()) {
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

        $this->info('Closing trainings for those who left subdivision...');
        $count = 0;

        foreach ($trainings as $training) {
            if (in_array($training->user->subdivision, $subdivisions)) {
                continue;
            }

            // Close the training
            $training->updateStatus(-4);
            $training->closed_reason = 'The student has left or is no longer part of our subdivision.';
            $training->save();

            // Notify student of closure
            $training->user->notify(new TrainingClosedNotification($training, -4, 'Student has left the subdivision.'));

            // Log the closure
            ActivityLogController::warning('TRAINING', 'Closed training request ' . $training->id . ' due to student leaving division');

            $count++;
        }

        $this->info($count . ' trainings affected.');

        // Make users who left subdivision inactive
        $this->info('Making users who left subdivision inactive...');
        $count = 0;

        $membersNotInSubdivision = User::whereNotIn('subdivision', $subdivisions)->get();
        $activeMembersNotInSubdivision = User::getActiveAtcMembers($membersNotInSubdivision->pluck('id')->toArray());

        $activeMembersNotInSubdivision->each(function ($member) use (&$count) {

            // Set as ATC Inactive
            $atcActivitiesToSetAsInactive = $member->atcActivity->where('atc_active', true);
            AtcActivity::whereIn('id', $atcActivitiesToSetAsInactive->pluck('id'))->update(['atc_active' => false]);

            $count++;
        });

        $this->info($count . ' users affected.');

        $this->info('Done');
    }
}
