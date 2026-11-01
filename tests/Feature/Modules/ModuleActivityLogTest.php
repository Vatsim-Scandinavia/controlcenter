<?php

namespace Tests\Feature\Modules;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityLog\LogNameRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\Fixtures\Modules\ExampleSubject;
use Tests\TestCase;

class ModuleActivityLogTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    #[Test]
    public function registered_module_log_names_merge_with_the_core_enum(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $labels = LogNameRegistry::labels();

        // Core enum categories are preserved.
        $this->assertSame('Booking', $labels['booking']);
        // The enabled module contributes its registered label.
        $this->assertSame('Example', $labels['modules.example']);
        $this->assertSame('Example', LogNameRegistry::label('modules.example'));
    }

    #[Test]
    public function a_disabled_module_contributes_no_log_name(): void
    {
        $this->bootModules([ExampleModule::class]); // discovered but disabled

        $this->assertArrayNotHasKey('modules.example', LogNameRegistry::labels());
    }

    #[Test]
    public function a_module_log_entry_renders_with_its_registered_label(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        activity('modules.example')->log('Did an example thing');

        $this->actingAs($this->admin())
            ->get(route('admin.logs'))
            ->assertOk()
            ->assertSee('Example')
            ->assertDontSee('Modules.example');
    }

    #[Test]
    public function a_subject_model_can_resolve_its_own_detail_route(): void
    {
        $log = new ActivityLog;
        $log->subject_type = ExampleSubject::class;
        $log->subject_id = 7;

        $this->assertSame('https://cc.test/modules/example/subjects/7', $log->subject_route);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }
}
