<?php

namespace Tests\Feature\Modules;

use App\Models\Area;
use App\Models\Module as ModuleRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

class ModuleAdminPageTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }

    #[Test]
    public function an_admin_can_view_the_modules_page(): void
    {
        $this->bootModules([ExampleModule::class]);

        $this->actingAs($this->admin())
            ->get(route('admin.modules'))
            ->assertOk()
            ->assertSee('Example Module');
    }

    #[Test]
    public function a_moderator_cannot_view_the_modules_page(): void
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $this->actingAs($moderator)->get(route('admin.modules'))->assertForbidden();
    }

    #[Test]
    public function a_director_is_denied_module_management_and_module_permissions(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        // Decision 7: director is denied modules.** and system.modules.manage.
        $this->assertFalse($director->can('system.modules.manage'));
        $this->assertFalse($director->can('modules.example.view'));

        // admin keeps the catch-all and so retains module permissions.
        $this->assertTrue($this->admin()->can('modules.example.view'));
    }

    #[Test]
    public function an_admin_can_enable_and_disable_a_module(): void
    {
        $this->bootModules([ExampleModule::class]);

        $this->actingAs($this->admin())
            ->post(route('admin.modules.enable', 'example'))
            ->assertRedirect(route('admin.modules'));

        $this->assertTrue(ModuleRecord::query()->where('slug', 'example')->value('enabled'));

        $this->actingAs($this->admin())
            ->post(route('admin.modules.disable', 'example'))
            ->assertRedirect(route('admin.modules'));

        $this->assertFalse((bool) ModuleRecord::query()->where('slug', 'example')->value('enabled'));
    }
}
