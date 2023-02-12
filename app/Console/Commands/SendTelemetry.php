<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class SendTelemetry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:telemetry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends telemetry data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        try{
            $req = Http::post('https://telemetry.vatsca.org', [
                'url' => Config::get('app.url'),
                'owner' => Config::get('app.owner'),
                'version' => Config::get('app.version')
            ]);

            if($req->clientError()){
                $this->warn('Telemetry request replied with client error');
                return;
            }

            if($req->serverError()){
                $this->warn('Telemetry request replied with server error');
                return;
            }

            $this->info('Telemetry successfully sent.');

        } catch(\Exception $e) {
            $this->warn('Telemetry service unavailable: '.$e->getMessage());
        }
        
    }
}
