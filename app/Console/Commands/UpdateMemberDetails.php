<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use anlutro\LaravelSettings\Facade as Setting;
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
        $mentors = User::query()->where('group', 3)->get();

        $bar = $this->output->createProgressBar($mentors->count());

        $bar->start();

        foreach ($mentors as $mentor) {

            if ($mentor->subdivision == Setting::get('trainingSubDivisions')) continue;

            DB::table('training_role_country')->where('user_id', $mentor->id)->delete();

            $mentor->group = null;
            $mentor->save();

            $bar->advance();
        }

        $bar->finish();
    }
}
