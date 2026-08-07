<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeedbackReportViewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }

    #[Test]
    public function feedback_text_is_html_escaped_on_the_report(): void
    {
        $position = Position::factory()->create();
        Feedback::factory()->create([
            'reference_position_id' => $position->id,
            'feedback' => '<script>alert(1)</script>',
        ]);

        $response = $this->actingAs($this->admin())->get(route('reports.feedback'));

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    #[Test]
    public function report_renders_a_single_shared_edit_modal(): void
    {
        $position = Position::factory()->create();
        Feedback::factory()->count(3)->create(['reference_position_id' => $position->id]);

        $response = $this->actingAs($this->admin())->get(route('reports.feedback'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'id="feedback-edit-modal"'));
    }
}
