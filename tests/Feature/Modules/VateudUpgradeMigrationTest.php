<?php

namespace Tests\Feature\Modules;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The upgrade migration carries the legacy division API integration over to the
 * vateud module, enabling it when the old toggle was on, and reverses cleanly.
 */
class VateudUpgradeMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): Migration
    {
        return require base_path('database/migrations/2026_11_02_000000_enable_vateud_module_from_division_api_setting.php');
    }

    private function settingsTable(): string
    {
        return (string) config('settings.table');
    }

    #[Test]
    public function it_enables_the_vateud_module_when_the_legacy_toggle_was_on(): void
    {
        DB::table($this->settingsTable())->updateOrInsert(['key' => 'divisionApiEnabled'], ['value' => 1]);
        DB::table('modules')->where('slug', 'vateud')->delete();

        $this->migration()->up();

        $this->assertTrue((bool) DB::table('modules')->where('slug', 'vateud')->value('enabled'));
        $this->assertDatabaseMissing($this->settingsTable(), ['key' => 'divisionApiEnabled']);
    }

    #[Test]
    public function it_leaves_the_module_disabled_when_the_legacy_toggle_was_off(): void
    {
        DB::table($this->settingsTable())->updateOrInsert(['key' => 'divisionApiEnabled'], ['value' => 0]);
        DB::table('modules')->where('slug', 'vateud')->delete();

        $this->migration()->up();

        $this->assertNull(DB::table('modules')->where('slug', 'vateud')->value('enabled'));
    }

    #[Test]
    public function down_restores_the_legacy_toggle_from_the_module_state(): void
    {
        DB::table('modules')->updateOrInsert(
            ['slug' => 'vateud'],
            ['enabled' => true, 'enabled_at' => now(), 'installed_version' => '1.0.0', 'updated_at' => now(), 'created_at' => now()],
        );

        $this->migration()->down();

        $this->assertSame(1, (int) DB::table($this->settingsTable())->where('key', 'divisionApiEnabled')->value('value'));
        $this->assertNull(DB::table('modules')->where('slug', 'vateud')->value('enabled'));
    }
}
