<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

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
        $uuid = \Ramsey\Uuid\Uuid::uuid5('151323ad-e7d1-4ed0-9c49-18a5cde076d8', Config::get('app.key') . Config::get('app.url'));

        try {
            $req = Http::post('https://telemetry.vatsca.org/v1/', [
                'service' => 'cc',
                'uuid' => $uuid,
                'url' => Config::get('app.url'),
                'owner' => Config::get('app.owner_name'),
                'version' => Config::get('app.version'),
                'env' => Config::get('app.env'),
            ]);

            if ($req->clientError()) {
                $this->warn('Telemetry request replied with client error: ' . $req->body());

                return;
            }

            if ($req->serverError()) {
                $this->warn('Telemetry request replied with server error ' . $req->body());

                return;
            }

            $this->info('Telemetry successfully sent.');
        } catch (\Exception $e) {
            $this->warn('Telemetry service unavailable: ' . $e->getMessage());
        }
    }
}
