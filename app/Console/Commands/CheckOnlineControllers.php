<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\Area;
use App\Models\Position;
use App\Models\User;
use App\Notifications\InactiveOnlineNotification;
use App\Notifications\InactiveOnlineStaffNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
        $this->info('Starting online controller check...');

        // Check if the setting is turned on
        if (! Setting::get('atcActivityNotifyInactive')) {
            return;
        }

        // Fetch which first four characters from ICAOs should look for based on positions database
        $areasRaw = DB::table('positions')
            ->select(DB::raw('DISTINCT LEFT(callsign, 4) as prefix'))
            ->get();

        $areas = collect();
        foreach ($areasRaw as $a) {
            $areas->push($a->prefix);
        }

        $areasRegex = '/(^' . $areas->implode('|^') . ")\w+(?<!OBS)$/";

        $this->info('Collecting online controllers...');

        // Fetch the latest URI to data feed
        $dataUri = Http::get('https://status.vatsim.net/status.json');
        if (! isset($dataUri) || ! isset($dataUri['data']) || ! isset($dataUri['data']['v3']) || ! isset($dataUri['data']['v3'][0])) {
            $this->info('No data URI found. Aborting.');

            return;
        }

        $dataReturn = Http::get($dataUri['data']['v3'][0]);

        if (isset($dataReturn) && isset($dataReturn['controllers'])) {
            $vatsimData = $dataReturn['controllers'];

            foreach ($vatsimData as $d) {

                // If the callsign matches our prefxies, but also double check it's not an observer by verifying the facility
                if (preg_match($areasRegex, $d['callsign']) && $d['facility'] != 0) {

                    // Lets check this user
                    $this->info('Checking user ' . $d['cid']);
                    $user = User::find($d['cid']);
                    $position = Position::firstWhere('callsign', $d['callsign']);
                    $area = (isset($position->area)) ? $position->area : null;

                    if (isset($user) && ! $user->isAllowedToControlOnline($area)) {
                        if (! isset($user->last_inactivity_warning) || (isset($user->last_inactivity_warning) && Carbon::now()->gt(Carbon::parse($user->last_inactivity_warning)->addHours(6)))) {
                            // Send warning to user
                            $user->notify(new InactiveOnlineNotification($user));
                            $this->info($user->name . ' is inactive. Sending notification.');
                            $user->last_inactivity_warning = now();
                            $user->save();

                            // Send warning to all admins, and moderators in selected area
                            $sendToStaff = User::allWithGroup(1);

                            if (isset($area)) {
                                $moderators = User::allWithGroup(2);
                                foreach ($moderators as $m) {
                                    if ($sendToStaff->where('id', $m->id)->count()) {
                                        continue;
                                    }

                                    if ($m->isModerator($area)) {
                                        $sendToStaff->push($m);
                                    }
                                }
                            }

                            $user->notify(new InactiveOnlineStaffNotification($sendToStaff, $user, $d['callsign'], $d['logon_time']));
                        } else {
                            $this->info($user->name . ' is inactive. Supressing notification due to one already been sent recently.');
                        }
                    }
                }
            }
        }

        return 0;
    }
}
