<?php

namespace Tests\Feature\Modules;

use App\Actions\GrantRole;
use App\Actions\RevokeRole;
use App\Events\V1\TaskCreated;
use App\Events\V1\TrainingCreated;
use App\Models\Area;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use App\Modules\ModuleRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\Fixtures\Modules\RegisterSpyModule;
use Tests\Fixtures\Modules\ThrowingModule;
use Tests\Fixtures\Modules\ThrowingRouteModule;
use Tests\TestCase;

/**
 * Regression tests for the second independent review re-check (findings 2, 6
 * and 8 were only partly fixed; several new items). The boot/route-cache items
 * that need a real fresh process live in {@see ModuleFreshBootTest}; everything
 * provable in-process lives here.
 */
class ReviewRecheckTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }

    // -- Item 1: a throwing route callback must not corrupt later modules -----

    #[Test]
    public function a_throwing_route_callback_does_not_corrupt_the_next_modules_routes(): void
    {
        // ThrowingRouteModule is discovered first; its web group throws. The
        // group stack must be restored so the example module (and core routes)
        // register under their own prefix/name, not the throwing module's.
        $this->bootModules(
            [ThrowingRouteModule::class, ExampleModule::class],
            ['throwroute', 'example'],
        );

        $this->assertTrue(Route::has('login'), 'A core route was lost.');
        $this->assertTrue(
            Route::has('modules.example.index'),
            'The example module route leaked under the throwing module prefix.',
        );
        $this->assertSame('modules/example', ltrim(route('modules.example.index', [], false), '/'));
    }

    // -- Item 2: privacy registration failures must not report success -------

    #[Test]
    public function deleting_a_user_fails_when_a_module_cannot_be_initialised(): void
    {
        // ThrowingModule is installed; its register() throws, so we cannot know
        // whether it holds the user's data. Deletion must not report success,
        // must keep the user, and must return a failing exit code.
        $this->bootModules([ThrowingModule::class]);

        $user = User::factory()->create();

        $this->artisan('user:delete')
            ->expectsQuestion("What is the user's CID?", $user->id)
            ->expectsChoice(
                'Do you want to PSEUDONYMISE or DELETE the user?',
                'PERMANENTLY DELETE -> Deletes all data related to this user, only applicable if user is banned from VATSIM and will never return',
                [
                    'PSEUDONYMISE -> Used for GDPR deletion requests from user directly to us',
                    'PERMANENTLY DELETE -> Deletes all data related to this user, only applicable if user is banned from VATSIM and will never return',
                ],
            )
            ->expectsConfirmation('Are you sure you want to PERMANENTLY DELETE ' . $user->name . ' (' . $user->id . ')? This is IRREVERSIBLE!', 'yes')
            ->assertExitCode(1);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    // -- Item 3: the admin settings page must not activate disabled modules --

    #[Test]
    public function the_settings_page_does_not_run_register_for_a_disabled_module(): void
    {
        RegisterSpyModule::$registerCount = 0;

        // Discovered but NOT enabled.
        $this->bootModules([RegisterSpyModule::class]);

        $this->assertSame(0, RegisterSpyModule::$registerCount, 'Boot should not register a disabled module.');

        $this->actingAs($this->admin())
            ->get(route('admin.modules.settings', RegisterSpyModule::SLUG))
            ->assertRedirect(route('admin.modules'));

        $this->assertSame(
            0,
            RegisterSpyModule::$registerCount,
            'The settings page must not run a disabled module\'s register() hook.',
        );
    }

    #[Test]
    public function submitting_settings_does_not_run_register_for_a_disabled_module(): void
    {
        RegisterSpyModule::$registerCount = 0;

        $this->bootModules([RegisterSpyModule::class]);

        $this->actingAs($this->admin())
            ->post(route('admin.modules.settings.store', RegisterSpyModule::SLUG), ['title' => 'x'])
            ->assertRedirect(route('admin.modules'));

        $this->assertSame(0, RegisterSpyModule::$registerCount);
    }

    // -- Item 6: creation events must expose persisted DB defaults -----------

    #[Test]
    public function the_training_created_event_sees_the_persisted_default_status(): void
    {
        $captured = 'unset';

        Event::listen(TrainingCreated::class, function (TrainingCreated $event) use (&$captured): void {
            $captured = $event->training->status;
        });

        $area = Area::factory()->create();
        $user = User::factory()->create();

        // Production creation pattern: no status given, so the DB default (0 /
        // IN_QUEUE) applies. The event must not expose a null status.
        $training = Training::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'area_id' => $area->id,
            'type' => 1,
            'motivation' => '',
            'english_only_training' => false,
        ]);
        $training->recordCreated();

        $this->assertNotNull($captured, 'TrainingCreated exposed a null status.');
        $this->assertSame($training->fresh()->status, $captured);
    }

    #[Test]
    public function the_task_created_event_sees_the_persisted_default_status(): void
    {
        $captured = 'unset';

        Event::listen(TaskCreated::class, function (TaskCreated $event) use (&$captured): void {
            $captured = $event->task->status;
        });

        $area = Area::factory()->create();
        $user = User::factory()->create();
        $training = Training::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'area_id' => $area->id,
            'type' => 1,
            'motivation' => '',
            'english_only_training' => false,
        ]);

        $task = Task::create([
            'type' => 'custom',
            'subject_user_id' => $user->id,
            'subject_training_id' => $training->id,
            'creator_user_id' => $user->id,
            'assignee_user_id' => $user->id,
        ]);

        $this->assertNotNull($captured, 'TaskCreated exposed a null status.');
        $this->assertSame($task->fresh()->status, $captured);
    }

    // -- Item 7: a disabled module's role assignment must stay revocable -----

    #[Test]
    public function a_module_role_can_be_revoked_after_the_module_is_disabled(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $actor = $this->admin();
        $target = User::factory()->create();

        app(GrantRole::class)($actor, $target, 'modules.example.reviewer', null);
        $this->assertSame(1, $target->roleAssignments()->count());

        // Disable the module: its role definition disappears from RoleSource.
        $registry = app(ModuleRegistry::class);
        $registry->disable('example');
        $registry->flush();

        // The dangling assignment must still be revocable by a module manager.
        app(RevokeRole::class)($actor, $target, 'modules.example.reviewer', null);

        $this->assertSame(0, $target->fresh()->roleAssignments()->count());
    }

    #[Test]
    public function a_role_with_no_definition_cannot_be_newly_granted(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $actor = $this->admin();
        $target = User::factory()->create();

        // No assignment exists, and the module is disabled, so this is a grant
        // of an undefined role and must still be rejected.
        $registry = app(ModuleRegistry::class);
        $registry->disable('example');
        $registry->flush();

        $this->expectException(AuthorizationException::class);
        app(GrantRole::class)($actor, $target, 'modules.example.reviewer', null);
    }

    // -- Item 8: module abilities must resolve dynamically against RoleSource -

    #[Test]
    public function a_module_ability_is_grantable_without_a_boot_time_gate_definition(): void
    {
        // Enable the module WITHOUT running InteractsWithModules' gate-define
        // loop: nothing defines a gate for modules.example.view after enabling.
        // The dynamic resolver must still authorise it.
        config(['modules.register' => [ExampleModule::class]]);
        config(['modules.safe_mode' => false]);

        $registry = app(ModuleRegistry::class);
        $registry->flush();
        $registry->enable('example');
        $registry->flush();

        $this->assertFalse(
            Gate::has('modules.example.view'),
            'Test premise: no explicit gate should be defined for this ability.',
        );

        $user = User::factory()->create();
        $user->roleAssignments()->create(['role' => 'modules.example.reviewer', 'area_id' => null]);

        $this->assertTrue(Gate::forUser($user->fresh())->allows('modules.example.view'));
    }
}
