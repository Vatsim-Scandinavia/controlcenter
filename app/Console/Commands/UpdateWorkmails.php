<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateWorkmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:workmails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expires workmails addresses';

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
     * @return int
     */
    public function handle()
    {
        // Check for expired workmails
        DB::table('users')->where('setting_workmail_expire', '<=', date('Y-m-d H:i:s'))->update(['setting_workmail_address' => null, 'setting_workmail_expire' => null]);

        // Check for users that no longer hold a moderator or admin rank
        foreach (User::whereNotNull('setting_workmail_address')->get() as $user) {
            if ($user->groups()->count() == 0 || (! $user->isModerator() && ! $user->isAdmin())) {
                $user->setting_workmail_address = null;
                $user->setting_workmail_expire = null;
                $user->save();
            }
        }

        $this->info('All expired workmails workmails have been cleaned.');
    }
}
