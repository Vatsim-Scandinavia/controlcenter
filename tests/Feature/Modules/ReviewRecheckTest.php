<?php

namespace Tests\Feature\Modules;

use App\Actions\GrantRole;
use App\Actions\RevokeRole;
use App\Contracts\DivisionApiContract;
use App\Events\V1\RoleGranted;
use App\Events\V1\TaskCreated;
use App\Events\V1\TrainingCreated;
use App\Livewire\UserRoles;
use App\Models\Area;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use App\Modules\ModuleRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\DuplicateExampleModule;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\Fixtures\Modules\RegisterSpyModule;
use Tests\Fixtures\Modules\ThrowingManifestModule;
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

        // The throwing module registered /half before throwing; that partial
        // route must not survive (staged routes are discarded on failure).
        $this->assertFalse(Route::has('modules.throwroute.half'), 'A partial route from a failed module stayed live.');
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

    #[Test]
    public function the_dynamic_resolver_defers_a_module_ability_with_a_model_argument(): void
    {
        // A modules.* ability checked with a non-Area (resource) argument must
        // not be answered by the permission resolver (which would TypeError);
        // it must reach the ability's own gate/policy.
        $this->bootModules([ExampleModule::class], ['example']);

        $sentinel = User::factory()->create();
        Gate::define('modules.example.resource', function (User $user, User $model) use ($sentinel): bool {
            return $model->is($sentinel);
        });

        $actor = User::factory()->create();
        $actor->roleAssignments()->create(['role' => 'modules.example.reviewer', 'area_id' => null]);

        // No TypeError, and the resource gate's own logic decides.
        $this->assertTrue(Gate::forUser($actor)->allows('modules.example.resource', $sentinel));
        $this->assertFalse(Gate::forUser($actor)->allows('modules.example.resource', $actor));
    }

    #[Test]
    public function an_explicit_module_gate_denial_is_not_overridden(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        // A module defines its own gate for a permission the user otherwise
        // holds, denying in this case. The dynamic resolver must not override it.
        Gate::define('modules.example.view', fn (User $user) => false);

        $user = User::factory()->create();
        $user->roleAssignments()->create(['role' => 'modules.example.reviewer', 'area_id' => null]);

        $this->assertTrue($user->fresh()->hasPermission('modules.example.view'));
        $this->assertFalse(Gate::forUser($user->fresh())->allows('modules.example.view'));
    }

    // -- P1-1: every installed candidate that falls out of discovery blocks ---

    #[Test]
    public function a_module_whose_manifest_throws_blocks_cleanup(): void
    {
        config(['modules.register' => [ThrowingManifestModule::class]]);
        config(['modules.safe_mode' => false]);
        $registry = app(ModuleRegistry::class);
        $registry->flush();

        $this->assertArrayHasKey(ThrowingManifestModule::class, $registry->discoveryFailures());
        $this->assertNotEmpty($registry->privacyCleanupPlan()['failed']);
    }

    #[Test]
    public function every_discovery_failure_mode_blocks_cleanup(): void
    {
        config([
            'modules.register' => [
                ThrowingManifestModule::class,          // manifest() throws
                'App\\DefinitelyNotAClass',             // missing class
                self::class,                            // not a Module implementation
                ExampleModule::class,                   // valid
                DuplicateExampleModule::class,          // duplicate 'example' slug
            ],
        ]);
        config(['modules.safe_mode' => false]);
        $registry = app(ModuleRegistry::class);
        $registry->flush();

        $failures = $registry->discoveryFailures();

        $this->assertArrayHasKey(ThrowingManifestModule::class, $failures);
        $this->assertArrayHasKey('App\\DefinitelyNotAClass', $failures);
        $this->assertArrayHasKey(self::class, $failures);
        $this->assertArrayHasKey(DuplicateExampleModule::class, $failures);

        // The one valid module still discovered; every failure surfaces as blocking.
        $this->assertArrayHasKey('example', $registry->discovered());
        foreach (array_keys($failures) as $class) {
            $this->assertArrayHasKey("class:{$class}", $registry->privacyCleanupPlan()['failed']);
        }
    }

    #[Test]
    public function deleting_a_user_fails_when_a_module_manifest_throws(): void
    {
        config(['modules.register' => [ThrowingManifestModule::class]]);
        config(['modules.safe_mode' => false]);
        app(ModuleRegistry::class)->flush();

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

    // -- P2-5: listeners must not see mutations that later roll back ----------

    #[Test]
    public function a_domain_event_in_a_rolled_back_transaction_is_not_delivered(): void
    {
        $delivered = 0;
        Event::listen(TaskCreated::class, function () use (&$delivered): void {
            $delivered++;
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

        try {
            DB::transaction(function () use ($user, $training): void {
                Task::create([
                    'type' => 'custom',
                    'subject_user_id' => $user->id,
                    'subject_training_id' => $training->id,
                    'creator_user_id' => $user->id,
                    'assignee_user_id' => $user->id,
                ]);

                throw new \RuntimeException('rolled back after the create');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(0, Task::count(), 'The task should have rolled back.');
        $this->assertSame(0, $delivered, 'TaskCreated was delivered for a rolled-back transaction.');
    }

    #[Test]
    public function a_partial_user_roles_grant_delivers_no_role_granted(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();

        // A moderator in area1 may grant 'buddy' there (roles.buddy.manage) but
        // not in area2. UserRoles::grant wraps the two grants in one transaction.
        $actor = User::factory()->create();
        $actor->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area1->id]);
        $target = User::factory()->create();

        // Count only the grant under test, not the actor's own setup assignment.
        $delivered = 0;
        Event::listen(RoleGranted::class, function () use (&$delivered): void {
            $delivered++;
        });

        $this->actingAs($actor);
        Livewire::test(UserRoles::class, ['user' => $target])
            ->set('selectedRole', 'buddy')
            ->set('selectedAreaIds', [$area1->id, $area2->id])
            ->call('grant');

        // The second grant fails authorization, so the transaction rolls back:
        // no assignment persists and no RoleGranted escapes.
        $this->assertSame(0, $target->fresh()->roleAssignments()->count());
        $this->assertSame(0, $delivered, 'RoleGranted leaked from a rolled-back multi-grant.');
    }

    // -- P3-6: getName is part of the division API contract -------------------

    #[Test]
    public function the_division_driver_exposes_a_name(): void
    {
        // NoOpAdapter is the default driver; it must satisfy the contract's
        // getName(), so DivisionApiError can name the driver without failing.
        $this->assertIsString(app(DivisionApiContract::class)->getName());
    }

    // -- P3-7: unknown CID keeps main's exit code -----------------------------

    #[Test]
    public function deleting_an_unknown_cid_exits_zero(): void
    {
        $this->artisan('user:delete')
            ->expectsQuestion("What is the user's CID?", '99999999')
            ->assertExitCode(0);
    }
}
