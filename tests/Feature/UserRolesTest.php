<?php

namespace Tests\Feature;

use App\Facades\DivisionApi;
use App\Livewire\UserRoles;
use App\Models\Area;
use App\Models\Training;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }

    #[Test]
    public function it_forbids_mounting_without_view_access(): void
    {
        $target = User::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(UserRoles::class, ['user' => $target])
            ->assertForbidden();
    }

    #[Test]
    public function it_lists_current_assignments_grouped_by_scope(): void
    {
        $area = Area::factory()->create(['name' => 'Enroute East']);
        $target = User::factory()->create();
        $target->roleAssignments()->create(['role' => 'staff', 'area_id' => null]);
        $target->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->assertSee('Global')
            ->assertSee('Staff')
            ->assertSee('Enroute East')
            ->assertSee('Moderator');
    }

    #[Test]
    public function it_shows_the_global_section_with_an_empty_note_when_the_user_has_no_assignments(): void
    {
        $target = User::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->assertSee('Global')
            ->assertSee('No global roles assigned')
            ->assertSee('No area roles assigned');
    }

    #[Test]
    public function admin_role_is_shown_without_a_remove_control(): void
    {
        $target = User::factory()->create();
        $target->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->assertSee('Administrator')
            ->assertDontSeeHtml('wire:click="confirmRemoval(\'admin\'');
    }

    #[Test]
    public function it_grants_an_area_role_to_multiple_areas_at_once(): void
    {
        $areaA = Area::factory()->create();
        $areaB = Area::factory()->create();
        $target = User::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('openAddModal')
            ->set('selectedRole', 'nav-editor')
            ->set('selectedAreaIds', [$areaA->id, $areaB->id])
            ->call('grant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role' => 'nav-editor', 'area_id' => $areaA->id]);
        $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role' => 'nav-editor', 'area_id' => $areaB->id]);
    }

    #[Test]
    public function granting_an_area_scoped_role_with_no_areas_ticked_does_not_report_success(): void
    {
        $area = Area::factory()->create();
        $target = User::factory()->create();

        $component = Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('openAddModal')
            ->set('selectedRole', 'nav-editor')
            // An unknown/stale area ID is dropped rather than resolving to null
            // and being mistaken for a global grant, so this is still "no options".
            ->set('selectedAreaIds', [$area->id + 999])
            ->call('grant');

        $this->assertDatabaseMissing('role_user', ['user_id' => $target->id, 'role' => 'nav-editor']);
        $component->assertSet('status', null)
            ->assertSet('error', 'Select at least one option to grant.')
            ->assertSet('showAddModal', true)
            ->assertSee('Select at least one option to grant.');
    }

    #[Test]
    public function grantable_roles_exclude_admin_and_roles_the_actor_cannot_grant(): void
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);
        $target = User::factory()->create();

        // Moderators may only grant mentor/buddy.
        $component = Livewire::actingAs($moderator)->test(UserRoles::class, ['user' => $target]);
        $grantable = array_keys($component->instance()->grantableRoles());

        $this->assertEqualsCanonicalizing(['mentor', 'buddy'], $grantable);
    }

    #[Test]
    public function grantable_roles_for_training_staff_are_mentor_and_buddy(): void
    {
        $area = Area::factory()->create();
        $trainingStaff = User::factory()->create();
        $trainingStaff->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $area->id]);
        $target = User::factory()->create();

        $component = Livewire::actingAs($trainingStaff)->test(UserRoles::class, ['user' => $target]);
        $grantable = array_keys($component->instance()->grantableRoles());

        $this->assertEqualsCanonicalizing(['mentor', 'buddy'], $grantable);
    }

    #[Test]
    public function global_option_for_an_area_only_role_is_not_applicable(): void
    {
        $target = User::factory()->create();

        $component = Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target]);

        $option = $component->instance()->globalOptionFor('mentor');

        $this->assertFalse($option['applicable']);
        $this->assertFalse($option['enabled']);
        $this->assertSame('Not available for this role', $option['reason']);
    }

    #[Test]
    public function area_options_for_marks_an_already_held_area_disabled_and_a_free_area_enabled(): void
    {
        $heldArea = Area::factory()->create();
        $openArea = Area::factory()->create();
        $target = User::factory()->create();
        $target->roleAssignments()->create(['role' => 'nav-editor', 'area_id' => $heldArea->id]);

        $component = Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target]);

        $options = collect($component->instance()->areaOptionsFor('nav-editor'))
            ->keyBy(fn (array $opt) => $opt['area']->id);

        $this->assertFalse($options[$heldArea->id]['enabled']);
        $this->assertSame('Already assigned', $options[$heldArea->id]['reason']);

        $this->assertTrue($options[$openArea->id]['enabled']);
        $this->assertNull($options[$openArea->id]['reason']);
    }

    #[Test]
    public function it_grants_a_global_role(): void
    {
        $target = User::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('openAddModal')
            ->set('selectedRole', 'staff')
            ->set('selectedGlobal', true)
            ->call('grant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role' => 'staff', 'area_id' => null]);
    }

    #[Test]
    public function add_modal_shows_the_reason_when_global_is_not_available_for_the_selected_role(): void
    {
        $target = User::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('openAddModal')
            ->set('selectedRole', 'mentor')
            ->assertSee('Not available for this role');
    }

    #[Test]
    public function add_modal_presents_two_labelled_steps_with_global_kept_distinct_from_areas(): void
    {
        Area::factory()->create();
        $target = User::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('openAddModal')
            ->assertSee('1. Select a role')
            ->assertSee('2. Select the area(s) of responsibility')
            ->assertSee(config('roles.roles.director.description'))
            ->set('selectedRole', 'director')
            ->assertSee('Organisation-wide')
            ->assertSee('Areas of responsibility');
    }

    #[Test]
    public function multi_area_mentor_grant_reports_partial_failure(): void
    {
        $ok = Area::factory()->create();
        $bad = Area::factory()->create();
        $target = User::factory()->create();

        DivisionApi::shouldReceive('assignMentor')->andReturnUsing(function () {
            static $calls = 0;
            $calls++;

            return $calls === 1
                ? false // success (no-op)
                : new \Illuminate\Http\Client\Response(new Response(422, [], json_encode(['message' => 'x'])));
        });
        DivisionApi::shouldReceive('getName')->andReturn('VATEUD');

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('openAddModal')
            ->set('selectedRole', 'mentor')
            ->set('selectedAreaIds', [$ok->id, $bad->id])
            ->call('grant');

        // Exactly one of the two mentor assignments persisted.
        $this->assertSame(1, $target->roleAssignments()->where('role', 'mentor')->count());
    }

    #[Test]
    public function removing_a_role_requires_confirmation_then_deletes(): void
    {
        $area = Area::factory()->create();
        $target = User::factory()->create();
        $target->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('confirmRemoval', 'moderator', $area->id)
            ->assertSet('pendingRemoval', ['role' => 'moderator', 'area_id' => $area->id])
            ->assertSee('Remove Moderator')
            ->assertSee('Remove Moderator in ' . $area->name . '?')
            ->call('remove');

        $this->assertDatabaseMissing('role_user', ['user_id' => $target->id, 'role' => 'moderator']);
    }

    #[Test]
    public function mentor_removal_shows_the_division_and_training_consequence(): void
    {
        $area = Area::factory()->create();
        $target = User::factory()->create();
        $target->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('confirmRemoval', 'mentor', $area->id)
            ->assertSee('Division API')
            ->assertSeeHtml('btn-danger');
    }

    #[Test]
    public function mentor_removal_in_one_of_two_areas_does_not_show_the_detach_clause(): void
    {
        $areaA = Area::factory()->create();
        $areaB = Area::factory()->create();
        $target = User::factory()->create();
        $target->roleAssignments()->create(['role' => 'mentor', 'area_id' => $areaA->id]);
        $target->roleAssignments()->create(['role' => 'mentor', 'area_id' => $areaB->id]);

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('confirmRemoval', 'mentor', $areaA->id)
            ->assertSee('Division API')
            ->assertSeeHtml('btn-danger')
            ->assertDontSee('detach');
    }

    #[Test]
    public function cancel_removal_clears_the_pending_removal(): void
    {
        $area = Area::factory()->create();
        $target = User::factory()->create();
        $target->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('confirmRemoval', 'moderator', $area->id)
            ->assertSet('pendingRemoval', ['role' => 'moderator', 'area_id' => $area->id])
            ->call('cancelRemoval')
            ->assertSet('pendingRemoval', null);
    }

    #[Test]
    public function removal_training_count_reflects_trainings_taught_in_the_area(): void
    {
        $area = Area::factory()->create();
        $target = User::factory()->create();
        $target->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);
        $training = Training::factory()->create(['area_id' => $area->id]);
        $target->teaches()->attach($training->id, ['expire_at' => now()->addMonths(12)]);

        $component = Livewire::actingAs($this->admin())
            ->test(UserRoles::class, ['user' => $target])
            ->call('confirmRemoval', 'mentor', $area->id);

        $this->assertSame(1, $component->instance()->removalTrainingCount());
        $component->assertSee('1 training(s)');
    }
}
