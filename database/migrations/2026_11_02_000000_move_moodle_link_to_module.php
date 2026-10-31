<?php

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

/**
 * Moves the former core `linkMoodle` global setting into the first-party Moodle
 * module. When a Moodle URL was configured, the module is enabled and the URL
 * is carried over to `modules.moodle.url`; the legacy setting is removed.
 */
return new class extends Migration
{
    private const string SLUG = 'moodle';

    private const string LEGACY_KEY = 'linkMoodle';

    private const string MODULE_KEY = 'modules.moodle.url';

    public function up(): void
    {
        $url = Setting::get(self::LEGACY_KEY);

        if (is_string($url) && $url !== '') {
            Setting::set(self::MODULE_KEY, $url);
            Setting::save();

            Module::query()->updateOrCreate(
                ['slug' => self::SLUG],
                ['enabled' => true, 'enabled_at' => now(), 'installed_version' => '1.0.0'],
            );
        }

        Setting::forget(self::LEGACY_KEY);
        Setting::save();
    }

    public function down(): void
    {
        $url = Setting::get(self::MODULE_KEY);

        if (is_string($url) && $url !== '') {
            Setting::set(self::LEGACY_KEY, $url);
            Setting::save();
        }

        Setting::forget(self::MODULE_KEY);
        Setting::save();

        Module::query()->where('slug', self::SLUG)->update(['enabled' => false]);
    }
};
