<?php

namespace App\Console\Commands;

use App\Helpers\VatsimRating;
use App\Models\Endorsement;
use App\Notifications\EndorsementExpiredNotification;
use Illuminate\Console\Command;

class CleanEndorsements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:endorsements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and clean expired training endorsements';

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
        $endorsements = Endorsement::whereNotNull('valid_to')->where('expired', false)->where('revoked', false)->where('valid_to', '<', date('Y-m-d H:i:s'))->get();
        foreach ($endorsements as $endorsement) {
            $endorsement->expired = true;
            $endorsement->save();

            // Only send e-mail notifications for training endorsements
            if ($endorsement->type == 'SOLO' || $endorsement->type == 'S1') {
                $endorsement->user->notify(new EndorsementExpiredNotification($endorsement));
            }
        }

        $this->info('All expired endorsements have been cleaned.');

        $permanentEndorsements = Endorsement::whereNull('valid_to')->where('type', 'S1')->where('expired', false)->where('revoked', false)->get();
        foreach ($permanentEndorsements as $endorsement) {
            if ($endorsement->user->rating >= VatsimRating::S2->value) {
                $endorsement->expired = true;
                $endorsement->valid_to = today();
                $endorsement->save();
            }
        }

        $this->info('All endorsements belonging to users who have achieved S2+ has been cleaned.');
    }
}
