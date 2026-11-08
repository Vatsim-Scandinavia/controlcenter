<?php

namespace Database\Seeders;

use App\Helpers\VatsimRating;
use App\Models\ApiKey;
use App\Models\Booking;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExternalBookingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('External booking demo data is only available in the local environment.');
        }

        $user = User::firstOrCreate(['id' => 10000010], [
            'first_name' => 'Demo',
            'last_name' => 'Controller',
            'email' => 'external-bookings@example.test',
            'rating' => VatsimRating::C1,
            'rating_short' => 'C1',
            'rating_long' => 'Controller',
            'region' => 'EMEA',
            'division' => 'EUD',
            'subdivision' => 'SCA',
            'last_login' => now(),
        ]);
        $user->roleAssignments()->firstOrCreate(['role' => 'admin', 'area_id' => null]);

        $position = Position::where('callsign', 'EKCH_TWR')->firstOrFail();
        $start = now()->addDay()->startOfDay()->addHours(18);
        foreach ([
            ['source' => 'EXTERNAL', 'integration_id' => 'staffing-adbj', 'external_id' => 'demo-staffing-assignment', 'external_revision' => 1, 'external_url' => 'https://staffing.adbj.no', 'event' => true],
            ['source' => 'EXTERNAL', 'integration_id' => 'another-roster', 'external_id' => 'demo-other-assignment', 'external_revision' => 1, 'external_url' => null, 'event' => true],
            ['source' => 'DISCORD', 'integration_id' => null, 'external_id' => null, 'event' => true],
            ['source' => 'CC', 'integration_id' => null, 'external_id' => null, 'event' => false],
        ] as $index => $metadata) {
            Booking::firstOrCreate([
                'callsign' => $position->callsign,
                'user_id' => $user->id,
                'time_start' => $start->copy()->addHours($index * 2),
            ], array_merge($metadata, [
                'position_id' => $position->id,
                'name' => $user->name,
                'time_end' => $start->copy()->addHours(($index + 1) * 2),
            ]));
        }

        $key = ApiKey::firstOrCreate(['name' => 'External booking local demo'], [
            'id' => (string) Str::uuid(),
            'integration_id' => 'staffing-adbj',
            'read_only' => false,
            'created_at' => now(),
        ]);
        Storage::disk('local')->put('external-booking-demo.json', json_encode([
            'base_url' => config('app.url'),
            'token' => $key->id,
            'payload' => [
                'cid' => $user->id,
                'date' => $start->format('d/m/Y'),
                'start_at' => '18:00',
                'end_at' => '20:00',
                'position' => $position->callsign,
                'source' => 'EXTERNAL',
                'external_id' => 'demo-staffing-assignment',
                'external_revision' => 2,
                'external_url' => 'https://staffing.adbj.no',
                'tag' => 3,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->command?->info('Demo bookings created. Log in locally as 10000010. API credentials and sample payload are in storage/app/external-booking-demo.json.');
    }
}
