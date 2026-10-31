<?php

namespace Tests\Feature\Modules;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\Module as ModuleRecord;
use App\Models\User;
use ControlCenter\Modules\Moodle\MoodleModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\TestCase;

class MoodleModuleTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    private const string URL = 'https://moodle.example.test/';

    #[Test]
    public function the_sidebar_link_appears_when_the_url_is_configured(): void
    {
        Setting::set('modules.moodle.url', self::URL);
        Setting::save();
        $this->bootModules([MoodleModule::class], ['moodle']);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('/modules/moodle');
    }

    #[Test]
    public function the_sidebar_link_is_hidden_when_no_url_is_configured(): void
    {
        $this->bootModules([MoodleModule::class], ['moodle']);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('/modules/moodle');
    }

    #[Test]
    public function the_module_route_redirects_to_the_configured_moodle(): void
    {
        Setting::set('modules.moodle.url', self::URL);
        Setting::save();
        $this->bootModules([MoodleModule::class], ['moodle']);

        $this->actingAs(User::factory()->create())
            ->get('/modules/moodle')
            ->assertRedirect(self::URL);
    }

    #[Test]
    public function the_url_is_configured_through_the_core_settings_page(): void
    {
        $this->bootModules([MoodleModule::class], ['moodle']);

        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->actingAs($admin)
            ->post(route('admin.modules.settings.store', 'moodle'), ['url' => self::URL])
            ->assertRedirect(route('admin.modules.settings', 'moodle'));

        $this->assertSame(self::URL, Setting::get('modules.moodle.url'));
    }

    #[Test]
    public function the_upgrade_migration_moves_the_legacy_link_into_the_module(): void
    {
        Setting::set('linkMoodle', self::URL);
        Setting::save();

        $migration = require database_path('migrations/2026_11_02_000000_move_moodle_link_to_module.php');
        $migration->up();

        $this->assertSame(self::URL, Setting::get('modules.moodle.url'));
        $this->assertTrue((bool) ModuleRecord::query()->where('slug', 'moodle')->value('enabled'));
        $this->assertEmpty(Setting::get('linkMoodle'));

        $migration->down();

        $this->assertSame(self::URL, Setting::get('linkMoodle'));
        $this->assertEmpty(Setting::get('modules.moodle.url'));
        $this->assertFalse((bool) ModuleRecord::query()->where('slug', 'moodle')->value('enabled'));
    }
}
