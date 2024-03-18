<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Command;

class UserMakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:makeadmin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make the specified user into an admin';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Input user
        $cid = $this->ask("What is the user's CID?");
        if ($user = User::find($cid)) {
            $this->info('User found: ' . $user->name);

            $areas = Area::all();
            $area = $this->choice('Which area? The admin has access across areas, but an area must be selected regardless.', $areas->pluck('name')->toArray());
            if ($area != null) {
                $area = Area::where('name', $area)->first();

                // Give the user permission to the area
                $user->groups()->attach(Group::find(1), ['area_id' => $area->id]);
                $this->info('User ' . $user->name . ' has been given admin permissions for ' . $area->name . '.');

                return Command::SUCCESS;
            } else {
                $this->error('No area was selected.');

                return Command::FAILURE;
            }

        } else {
            $this->error('No records of ' . $cid . ' was found.');

            return Command::FAILURE;
        }

        return Command::FAILURE;
    }
}
