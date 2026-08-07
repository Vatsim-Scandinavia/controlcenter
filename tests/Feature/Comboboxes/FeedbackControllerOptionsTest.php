<?php

namespace Tests\Feature\Comboboxes;

use App\Models\Area;
use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use App\Support\Comboboxes\FeedbackControllerOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreatesRoleAssignedUsers;
use Tests\TestCase;

class FeedbackControllerOptionsTest extends TestCase
{
    use CreatesRoleAssignedUsers;
    use RefreshDatabase;

    private function provider(): FeedbackControllerOptions
    {
        return app(FeedbackControllerOptions::class);
    }

    #[Test]
    public function it_returns_matching_controllers_shaped_as_value_and_label(): void
    {
        $position = Position::factory()->create();
        $ctrl = User::factory()->create(['first_name' => 'Zebediah', 'last_name' => 'Controller']);
        Feedback::factory()->create(['reference_position_id' => $position->id, 'reference_user_id' => $ctrl->id]);

        $this->actingAs($this->admin());

        $options = $this->provider()->options('Zeb');

        $this->assertCount(1, $options);
        $this->assertSame('Zebediah Controller', $options->first()['value']);
        $this->assertSame("Zebediah Controller ({$ctrl->id})", $options->first()['label']);
    }

    #[Test]
    public function it_matches_on_the_full_name_across_the_space(): void
    {
        // Guards the portable concat(): a search spanning first and last name
        // must match, which separate first_name/last_name conditions cannot do.
        $position = Position::factory()->create();
        $ctrl = User::factory()->create(['first_name' => 'Zebediah', 'last_name' => 'Controller']);
        Feedback::factory()->create(['reference_position_id' => $position->id, 'reference_user_id' => $ctrl->id]);

        $this->actingAs($this->admin());

        $options = $this->provider()->options('Zebediah Controller');

        $this->assertCount(1, $options);
        $this->assertSame('Zebediah Controller', $options->first()['value']);
    }

    #[Test]
    public function it_excludes_controllers_only_referenced_by_out_of_scope_feedback(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();
        $moderator = $this->moderatorFor($area1);

        $hiddenCtrl = User::factory()->create(['first_name' => 'Hidden', 'last_name' => 'Person']);
        $outOfScopePosition = Position::factory()->create(['area_id' => $area2->id]);
        Feedback::factory()->create(['reference_position_id' => $outOfScopePosition->id, 'reference_user_id' => $hiddenCtrl->id]);

        $this->actingAs($moderator);

        $this->assertTrue($this->provider()->options('Hidden')->isEmpty());
    }

    #[Test]
    public function it_orders_results_by_name(): void
    {
        // Guards the real-column ordering (first_name/last_name, never the
        // `name` accessor, which is not a DB column).
        $position = Position::factory()->create();
        $charlie = User::factory()->create(['first_name' => 'Charlie', 'last_name' => 'Zynq']);
        $alice = User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Zynq']);
        $bob = User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Zynq']);
        foreach ([$charlie, $alice, $bob] as $controller) {
            Feedback::factory()->create(['reference_position_id' => $position->id, 'reference_user_id' => $controller->id]);
        }

        $this->actingAs($this->admin());

        $this->assertSame(
            ['Alice Zynq', 'Bob Zynq', 'Charlie Zynq'],
            $this->provider()->options('Zynq')->pluck('value')->all(),
        );
    }

    #[Test]
    public function it_matches_by_id_for_the_deeplink(): void
    {
        $position = Position::factory()->create();
        $ctrl = User::factory()->create();
        Feedback::factory()->create(['reference_position_id' => $position->id, 'reference_user_id' => $ctrl->id]);

        $this->actingAs($this->admin());

        $options = $this->provider()->options((string) $ctrl->id);

        $this->assertCount(1, $options);
        $this->assertSame($ctrl->name, $options->first()['value']);
    }

    #[Test]
    public function it_limits_the_number_of_returned_options(): void
    {
        $position = Position::factory()->create();
        foreach (range(1, 20) as $i) {
            $ctrl = User::factory()->create(['first_name' => 'Common', 'last_name' => "Name{$i}"]);
            Feedback::factory()->create(['reference_position_id' => $position->id, 'reference_user_id' => $ctrl->id]);
        }

        $this->actingAs($this->admin());

        $this->assertSame(FeedbackControllerOptions::LIMIT, $this->provider()->options('Common')->count());
    }
}
