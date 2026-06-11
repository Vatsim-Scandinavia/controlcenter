<?php

namespace App\Console\Commands;

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
     */
    public function handle(): int
    {
        $cid = $this->ask("What is the user's CID?");

        $user = User::find($cid);
        if (! $user) {
            $this->error('No records of ' . $cid . ' was found.');

            return Command::FAILURE;
        }

        $this->info('User found: ' . $user->name);

        if ($user->hasGlobalRole('admin')) {
            $this->info($user->name . ' is already an administrator.');

            return Command::SUCCESS;
        }

        $user->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
        $this->info('User ' . $user->name . ' has been given system-wide admin permissions.');

        return Command::SUCCESS;
    }
}
