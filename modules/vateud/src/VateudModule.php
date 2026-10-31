<?php

namespace ControlCenter\Modules\Vateud;

use anlutro\LaravelSettings\Facade as Setting;
use App\Contracts\DivisionApiContract;
use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;
use ControlCenter\ModuleApi\Settings\SettingsSchema;
use ControlCenter\Modules\Vateud\Commands\SyncEndorsements;
use ControlCenter\Modules\Vateud\Commands\SyncRoster;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * VATEUD division API integration, packaged as a first-party module.
 *
 * Registers the VATEUD driver for Control Center's division API contract, the
 * roster/endorsement sync commands and their schedule (which only runs while
 * the module is enabled), and the admin settings that hold the endpoint and the
 * encrypted API secret.
 */
class VateudModule implements Module
{
    public const string SLUG = 'vateud';

    /** Setting key for the VATEUD API base URL. */
    public const string SETTING_API_URL = 'modules.vateud.api_url';

    /** Setting key for the VATEUD API secret (stored encrypted). */
    public const string SETTING_API_KEY = 'modules.vateud.api_key';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: self::SLUG,
            name: 'VATEUD',
            version: '1.0.0',
            ccVersion: '*',
            description: 'Synchronises roster, endorsements and exams with the VATEUD division API.',
            author: 'Control Center',
            permissions: [],
            dataHandling: [
                // VATEUD stores no Control Center user data of its own; it
                // transmits member CIDs to the external VATEUD division API so
                // operators know their data leaves the instance.
                'Transmits member VATSIM CIDs, ratings and endorsements to the external VATEUD division API.',
            ],
        );
    }

    public function register(Registrar $r): void
    {
        $r->driver(DivisionApiContract::class, 'vateud', fn (Application $app): VateudAdapter => new VateudAdapter(
            self::resolveApiUrl(),
            self::resolveApiToken(),
        ))
            ->command(SyncRoster::class)
            ->command(SyncEndorsements::class)
            ->schedule(function (Schedule $schedule): void {
                // Mirror the cadence core previously drove from update:atc:status.
                $schedule->command('vateud:sync-roster')->dailyAt('01:30');
                $schedule->command('vateud:sync-endorsements')->dailyAt('01:45');
            })
            ->adminSettings(
                (new SettingsSchema)
                    ->text('api_url', 'VATEUD API base URL', ['nullable', 'string', 'url'])
                    ->secret('api_key', 'VATEUD API key', ['nullable', 'string'])
            );
    }

    /**
     * Resolve the configured VATEUD base URL: the module setting wins, falling
     * back to the legacy environment variable for a not-yet-migrated instance.
     */
    public static function resolveApiUrl(): ?string
    {
        $url = Setting::get(self::SETTING_API_URL);

        return is_string($url) && $url !== '' ? $url : env('VATSIM_DIVISION_API_URL');
    }

    /**
     * Resolve the VATEUD API secret, decrypting the stored value.
     *
     * NOTE: the shared encrypted-settings helper is being built by another
     * slice. Until it lands, the secret is stored via Laravel's Crypt (the
     * SettingsSchema `secret` field above declares the intent) and decrypted
     * here; a plain value (e.g. the legacy env fallback) is returned as-is.
     */
    public static function resolveApiToken(): ?string
    {
        $stored = Setting::get(self::SETTING_API_KEY);

        if (is_string($stored) && $stored !== '') {
            try {
                return Crypt::decryptString($stored);
            } catch (Throwable) {
                // Not an encrypted payload (e.g. seeded in plain text); use it directly.
                return $stored;
            }
        }

        return env('VATSIM_DIVISION_API_TOKEN');
    }
}
