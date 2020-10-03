<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use anlutro\LaravelSettings\Facade as Setting;
use App\Training;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Notifications\TrainingClosedNotification;

class UpdateMemberDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:members';

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
     * @return mixed
     */
    public function handle()
    {
        $mentors = User::where('group', 3)->get();

        $subdivisions = array_map('trim', explode(',', Setting::get('trainingSubDivisions')));

        $this->info("Detaching mentors who no longer are in subdivision...");

        // Start the loop
        foreach ($mentors as $mentor) {

            if (in_array($mentor->subdivision, $subdivisions)) continue;

            // Remove any active trainings and training roles
            $mentor->teaches()->detach();
            $mentor->training_role_countries()->detach();

            // Remove mentor usergroup
            $mentor->group = null;
            $mentor->save();
        }

        // Get active trainings
        $trainings = Training::where('status', '>=', 0)->where('type', '!=', 5)->get();

        $this->info("Closing trainings for those who left subdivision...");

        foreach ($trainings as $training) {

            if (in_array($training->user->subdivision, $subdivisions)) continue;

            // Close the training
            $training->updateStatus(-4);
            $training->closed_reason = 'Student has left the subdivision.';
            $training->save();

            // Notify student of closure
            $training->user->notify(new TrainingClosedNotification($training, -4, 'Student has left the subdivision.'));
            
        }

        $this->info("Done");
    }
}
