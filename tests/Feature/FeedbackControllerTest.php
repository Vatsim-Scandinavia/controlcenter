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
    public function feedback_submission_is_rejected_when_text_exceeds_the_limit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('feedback.store'), [
            'feedback' => str_repeat('a', 16001),
        ]);

        $response->assertSessionHasErrors('feedback');
    }
}
