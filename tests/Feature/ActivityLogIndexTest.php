<?php

namespace Tests\Feature;

use App\Helpers\ActivityLevel;
use App\Models\ActivityLog;
use App\Models\Feedback;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogIndexTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }

    public function test_authorized_user_can_view_the_log(): void
    {
        ActivityLog::create(['log_name' => 'access', 'description' => 'A notable login event']);

        $this->actingAs($this->admin())
            ->get(route('admin.logs'))
            ->assertOk()
            ->assertSee('A notable login event');
    }

    public function test_unauthorized_user_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.logs'))
            ->assertForbidden();
    }

    public function test_filters_by_level(): void
    {
        ActivityLog::create(['log_name' => 'other', 'description' => 'Routine info entry', 'level' => ActivityLevel::Info]);
        ActivityLog::create(['log_name' => 'other', 'description' => 'Dangerous danger entry', 'level' => ActivityLevel::Danger]);

        $this->actingAs($this->admin())
            ->get(route('admin.logs', ['level' => 'danger']))
            ->assertOk()
            ->assertSee('Dangerous danger entry')
            ->assertDontSee('Routine info entry');
    }

    public function test_filters_by_category(): void
    {
        ActivityLog::create(['log_name' => 'training', 'description' => 'Training category entry']);
        ActivityLog::create(['log_name' => 'access', 'description' => 'Access category entry']);

        $this->actingAs($this->admin())
            ->get(route('admin.logs', ['log_name' => 'training']))
            ->assertOk()
            ->assertSee('Training category entry')
            ->assertDontSee('Access category entry');
    }

    public function test_renders_a_link_to_a_known_subject(): void
    {
        ActivityLog::create([
            'log_name' => 'training',
            'description' => 'Training updated',
            'subject_type' => Training::class,
            'subject_id' => 42,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.logs'))
            ->assertOk()
            ->assertSee(route('training.show', 42));
    }

    public function test_shows_system_when_there_is_no_causer(): void
    {
        ActivityLog::create(['log_name' => 'other', 'description' => 'System generated entry']);

        $this->actingAs($this->admin())
            ->get(route('admin.logs'))
            ->assertOk()
            ->assertSee('SYSTEM');
    }

    public function test_category_filter_lists_distinct_log_names(): void
    {
        ActivityLog::create(['log_name' => 'feedback', 'description' => 'Feedback updated']);
        ActivityLog::create(['log_name' => 'sector', 'description' => 'Position updated']);

        $this->actingAs($this->admin())
            ->get(route('admin.logs'))
            ->assertOk()
            ->assertSee('Feedback')
            ->assertSee('Sector');
    }

    public function test_feedback_reference_ids_are_rendered_as_labelled_names(): void
    {
        $controller = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Controller']);

        // Feedback (the subject) declares how its reference keys are presented,
        // so the generic view resolves the id to a labelled name with no
        // feedback-specific code in the controller or view.
        ActivityLog::create([
            'log_name' => 'feedback',
            'description' => 'Feedback updated',
            'subject_type' => Feedback::class,
            'subject_id' => 1,
            'attribute_changes' => [
                'attributes' => ['reference_user_id' => $controller->id],
                'old' => ['reference_user_id' => null],
            ],
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.logs'))
            ->assertOk()
            ->assertSee('Controller:')
            ->assertSee('Jane Controller (' . $controller->id . ')')
            ->assertDontSee('reference_user_id');
    }

    public function test_undeclared_changes_fall_back_to_raw_key_and_value(): void
    {
        // A log whose subject does not describe its changes renders generically.
        ActivityLog::create([
            'log_name' => 'sector',
            'description' => 'Position updated',
            'attribute_changes' => [
                'attributes' => ['callsign' => 'EKCH_APP'],
                'old' => ['callsign' => 'EKCH_A_APP'],
            ],
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.logs'))
            ->assertOk()
            ->assertSee('callsign:')
            ->assertSee('EKCH_A_APP')
            ->assertSee('EKCH_APP');
    }
}
