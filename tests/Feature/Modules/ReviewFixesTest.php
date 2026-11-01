<?php

namespace Tests\Feature\Modules;

use anlutro\LaravelSettings\Facade as Setting;
use App\Contracts\DivisionApiContract;
use App\Events\V1\TaskCreated;
use App\Models\Task;
use App\Models\User;
use App\Modules\Exceptions\ModuleRegistrationException;
use App\Modules\ModuleRegistrar;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use App\Support\DomainEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

/**
 * Regression tests for the independent review findings that are unit/feature
 * testable in-process. The boot findings (1, 2, 3) have their own fresh-process
 * tests in {@see ModuleFreshBootTest}.
 */
class ReviewFixesTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    #[Test]
    public function finding_12_a_bare_namespace_root_is_rejected_for_permissions_but_allowed_for_log_names(): void
    {
        $registrar = new ModuleRegistrar('example');

        // Permissions and API scopes must name something beneath the root.
        $this->expectException(ModuleRegistrationException::class);
        $registrar->permission('modules.example', 'Too broad');
    }

    #[Test]
    public function finding_12_the_namespace_root_is_allowed_for_activity_log_categories(): void
    {
        $registrar = new ModuleRegistrar('example');

        $registrar->activityLogName('modules.example', 'Example');
        $registrar->permission('modules.example.view', 'Fine');

        $this->assertArrayHasKey('modules.example', $registrar->activityLogNames);
        $this->assertArrayHasKey('modules.example.view', $registrar->permissions);
    }

    #[Test]
    public function finding_11_the_division_driver_is_matched_case_sensitively(): void
    {
        Setting::set('divisionApiEnabled', true);

        // Lowercase does not match the 'VATEUD' built-in driver, so it must not
        // resolve a real adapter (as on main).
        config(['vatsim.division_api_driver' => 'vateud']);
        $this->assertInstanceOf(NoOpAdapter::class, app(DivisionApiContract::class));

        app()->forgetInstance(DivisionApiContract::class);

        config(['vatsim.division_api_driver' => 'VATEUD']);
        $this->assertNotInstanceOf(NoOpAdapter::class, app(DivisionApiContract::class));
    }

    #[Test]
    public function finding_5_a_module_role_is_assignable_and_grants_its_permission(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $user = User::factory()->create();

        // The fixture registers role modules.example.reviewer (global) granting
        // modules.example.view. Assignment must not throw "not a recognised role".
        $user->roleAssignments()->create(['role' => 'modules.example.reviewer', 'area_id' => null]);

        $this->assertTrue($user->fresh()->hasPermission('modules.example.view'));
    }

    #[Test]
    public function finding_5_an_unknown_role_is_still_rejected(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $user->roleAssignments()->create(['role' => 'modules.example.ghost', 'area_id' => null]);
    }

    #[Test]
    public function finding_4_a_secret_is_not_flashed_to_old_input_on_validation_failure(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        // greeting has rule max:255; send an over-long value to fail validation,
        // along with a secret api_key that must not be flashed back.
        $response = $this->actingAs($admin)->post(route('admin.modules.settings.store', 'example'), [
            'greeting' => str_repeat('x', 300),
            'api_key' => 'super-secret-token',
            'enabled_flag' => '1',
        ]);

        $response->assertSessionHasErrors('greeting');
        $this->assertNull(session()->getOldInput('api_key'), 'The secret was flashed to old input.');
        // Non-secret input is still flashed for redisplay.
        $this->assertSame('1', session()->getOldInput('enabled_flag'));
    }

    #[Test]
    public function finding_9_a_failing_listener_does_not_suppress_the_others(): void
    {
        $ran = [];

        Event::listen(TaskCreated::class, function () {
            throw new \RuntimeException('boom');
        });
        Event::listen(TaskCreated::class, function () use (&$ran) {
            $ran[] = 'second';
        });

        // Build a TaskCreated without touching the database.
        $event = new TaskCreated(new Task);
        DomainEvents::dispatch($event);

        $this->assertSame(['second'], $ran);
    }
}
