<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function area_moderator_can_update_feedback_in_their_area(): void
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $position = Position::factory()->create(['area_id' => $area->id]);
        $feedback = Feedback::factory()->create(['reference_position_id' => $position->id]);

        $response = $this->actingAs($moderator)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => '',
        ]);

        $response->assertRedirect(route('reports.feedback'));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function area_moderator_cannot_update_feedback_in_another_area(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();

        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area1->id]);

        $position = Position::factory()->create(['area_id' => $area2->id]);
        $feedback = Feedback::factory()->create(['reference_position_id' => $position->id]);

        $response = $this->actingAs($moderator)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => '',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function area_moderator_can_update_uncorrelated_feedback(): void
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $feedback = Feedback::factory()->uncorrelated()->create();

        $response = $this->actingAs($moderator)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => '',
        ]);

        $response->assertRedirect(route('reports.feedback'));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function user_without_role_cannot_update_feedback(): void
    {
        $feedback = Feedback::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => '',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function update_cannot_change_feedback_text_or_submitter(): void
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $position = Position::factory()->create(['area_id' => $area->id]);
        $feedback = Feedback::factory()->create(['reference_position_id' => $position->id]);
        $originalText = $feedback->feedback;
        $originalSubmitter = $feedback->submitter_user_id;

        $this->actingAs($moderator)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => $position->callsign,
            'feedback' => 'HACKED TEXT',
            'submitter_user_id' => 999999,
        ]);

        $feedback->refresh();
        $this->assertSame($originalText, $feedback->feedback);
        $this->assertEquals($originalSubmitter, $feedback->submitter_user_id);
    }

    #[Test]
    public function explicit_area_can_be_set_when_no_position_is_referenced(): void
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);
        $feedback = Feedback::factory()->uncorrelated()->create();

        $this->actingAs($moderator)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => '',
            'area' => $area->id,
        ])->assertSessionHas('success');

        $feedback->refresh();
        $this->assertSame($area->id, $feedback->area_id);
        $this->assertTrue($feedback->area->is($area));
    }

    #[Test]
    public function explicit_area_is_rejected_when_a_position_is_also_provided(): void
    {
        $area = Area::factory()->create();
        $position = Position::factory()->create(['area_id' => $area->id]);
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);
        $feedback = Feedback::factory()->uncorrelated()->create();

        $this->actingAs($moderator)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => $position->callsign,
            'area' => $area->id,
        ])->assertSessionHasErrors('area');

        $this->assertNull($feedback->fresh()->area_id);
    }

    #[Test]
    public function assigning_feedback_to_another_area_hands_it_over_and_drops_the_editors_access(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();

        $moderator1 = User::factory()->create();
        $moderator1->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area1->id]);
        $moderator2 = User::factory()->create();
        $moderator2->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area2->id]);

        $feedback = Feedback::factory()->uncorrelated()->create(['area_id' => $area1->id]);
        $this->assertTrue($moderator1->can('update', $feedback));
        $this->assertFalse($moderator2->can('update', $feedback));

        $this->actingAs($moderator1)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => '',
            'area' => $area2->id,
        ])->assertSessionHas('success');

        $feedback->refresh();
        $this->assertSame($area2->id, $feedback->area_id);

        $this->assertTrue($moderator2->can('update', $feedback));
        $this->assertFalse($moderator1->can('update', $feedback));

        $this->actingAs($moderator1)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => '',
            'area' => $area1->id,
        ])->assertForbidden();
    }

    #[Test]
    public function setting_a_position_clears_a_previously_assigned_area(): void
    {
        $area = Area::factory()->create();
        $position = Position::factory()->create(['area_id' => $area->id]);
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $feedback = Feedback::factory()->uncorrelated()->create(['area_id' => $area->id]);

        $this->actingAs($moderator)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => $position->callsign,
        ])->assertSessionHas('success');

        $feedback->refresh();
        $this->assertNull($feedback->area_id);
        $this->assertSame($position->id, $feedback->reference_position_id);
        $this->assertTrue($feedback->area->is($area));
    }

    #[Test]
    public function an_explicitly_assigned_feedback_is_not_governed_by_the_uncorrelated_permission(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();

        // This moderator may edit uncorrelated feedback, but not one assigned
        // to another area.
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area1->id]);

        $feedback = Feedback::factory()->uncorrelated()->create(['area_id' => $area2->id]);

        $this->actingAs($moderator)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => '',
        ])->assertForbidden();
    }

    #[Test]
    public function feedback_submission_is_rejected_when_text_exceeds_the_limit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('feedback.store'), [
            'feedback' => str_repeat('a', 16001),
        ]);

        $response->assertSessionHasErrors('feedback');
    }
}
