<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Carries the legacy core division API integration over to the vateud module.
 *
 * Before modules, VATEUD was selected by the `divisionApiEnabled` setting plus
 * the VATSIM_DIVISION_API_* environment. This enables the vateud module when
 * that integration was on (or the driver was VATEUD), copies the endpoint and
 * secret into the module's settings, and drops the now-unused setting.
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = (string) Config::get('settings.table');

        $legacy = DB::table($settings)->where('key', 'divisionApiEnabled')->value('value');
        $wasEnabled = $legacy !== null && (bool) $legacy;
        $driver = env('VATSIM_DIVISION_API_DRIVER');
        $shouldEnable = $wasEnabled || strcasecmp((string) $driver, 'VATEUD') === 0;

        // Carry the endpoint and secret into the module's settings so the
        // module keeps working without any env change. Harmless when unset.
        if (($url = env('VATSIM_DIVISION_API_URL')) !== null && $url !== '') {
            $this->putSetting($settings, 'modules.vateud.api_url', $url);
        }

        if (($token = env('VATSIM_DIVISION_API_TOKEN')) !== null && $token !== '') {
            $this->putSetting($settings, 'modules.vateud.api_key', Crypt::encryptString($token));
        }

        if ($shouldEnable) {
            DB::table('modules')->updateOrInsert(
                ['slug' => 'vateud'],
                [
                    'enabled' => true,
                    'enabled_at' => now(),
                    'installed_version' => '1.0.0',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        DB::table($settings)->where('key', 'divisionApiEnabled')->delete();
    }

    public function down(): void
    {
        $settings = (string) Config::get('settings.table');

        $wasEnabled = (bool) DB::table('modules')->where('slug', 'vateud')->value('enabled');

        // Restore the legacy toggle reflecting the module's state.
        DB::table($settings)->updateOrInsert(
            ['key' => 'divisionApiEnabled'],
            ['value' => $wasEnabled ? 1 : 0],
        );

        // Remove the module record and the carried-over settings.
        DB::table('modules')->where('slug', 'vateud')->delete();
        DB::table($settings)->whereIn('key', ['modules.vateud.api_url', 'modules.vateud.api_key'])->delete();
    }

    private function putSetting(string $table, string $key, string $value): void
    {
        DB::table($table)->updateOrInsert(['key' => $key], ['value' => $value]);
    }
};
