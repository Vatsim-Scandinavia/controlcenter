<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\InactiveOnlineNotification;
use App\Notifications\InactiveOnlineStaffNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckOnlineControllers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:controllers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitors the online controllers';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Fetch which country ICAOs we should look for based on positions database
        $areasRaw = DB::select(
            DB::raw('SELECT DISTINCT LEFT(callsign, 2) as prefix FROM positions;')
        );

        $areas = collect();
        foreach($areasRaw as $a){
            $areas->push($a->prefix);
        }

        $areasRegex = "/(^".$areas->implode('|^').")\w+/";

        // Fetch the latest URI to data feed
        $dataUri = json_decode(file_get_contents('https://status.vatsim.net/status.json'))->data->v3[0];
        $vatsimData = json_decode(file_get_contents($dataUri))->controllers;
        
        foreach($vatsimData as $d){
            if(preg_match($areasRegex, $d->callsign)){
                // Lets check this user
                $user = User::find(10000010);
                if(isset($user)){
                    if(!$user->active && !$user->hasActiveTrainings() && !$user->isVisiting()){
                        // Send warning to user
                        $user->notify(new InactiveOnlineNotification($user));

                        // Send warning to all staff
                        $moderators = User::allWithGroup(2, '<=');
                        Notification::send($moderators, new InactiveOnlineStaffNotification($user, $d->callsign, $d->logon_time));
                    }
                }
                return 0;
            }
        }
        
        return 0;
    }
}
