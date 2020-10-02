<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use anlutro\LaravelSettings\Facade as Setting;
use App\Training;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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

        $this->info("Starting removing mentors");

        //start the loop
        foreach ($mentors as $mentor) {

            if ($mentor->subdivision == Setting::get('trainingSubDivisions')) continue;

            // remove any active trainings
            $user->teaches()->detach();

            // remove training roles in countries
            $user->training_role_countries()->detach();

            // change user group to null
            $mentor->group = null;
            $mentor->save();
        }

        $this->info("Done removing mentors.");

        // get active trainings
        $trainings = Training::where('status', '>=', 0)->get();

        $this->info("Deleting trainings.");

        foreach ($trainings as $training) {

            if ($training->user->handover->subdivision == Setting::get('trainingSubDivisions')) continue;

            // change it's status
            $training->status = -4;
            $training->save();

            // TODO: Add notifications

        }

        $this->info("Done");
    }
}
