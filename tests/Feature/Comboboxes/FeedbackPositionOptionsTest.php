<?php

namespace Tests\Feature\Comboboxes;

use App\Models\Area;
use App\Models\Position;
use App\Support\Comboboxes\FeedbackPositionOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreatesRoleAssignedUsers;
use Tests\TestCase;

class FeedbackPositionOptionsTest extends TestCase
{
    use CreatesRoleAssignedUsers;
    use RefreshDatabase;

    private function provider(): FeedbackPositionOptions
    {
        return app(FeedbackPositionOptions::class);
    }

    #[Test]
    public function it_returns_matching_positions_shaped_as_value_and_label(): void
    {
        // Use a token that cannot collide with the app's seeded positions.
        Position::factory()->create(['callsign' => 'QZZZ_TWR', 'name' => 'Testfield Tower']);

        $this->actingAs($this->admin());

        $options = $this->provider()->options('QZZZ');

        $this->assertCount(1, $options);
        $this->assertSame('QZZZ_TWR', $options->first()['value']);
        $this->assertSame('QZZZ_TWR - Testfield Tower', $options->first()['label']);
    }

    #[Test]
    public function it_excludes_positions_outside_the_users_area_scope(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();
        $moderator = $this->moderatorFor($area1);

        Position::factory()->create(['area_id' => $area2->id, 'callsign' => 'ENBR_APP']);

        $this->actingAs($moderator);

        $this->assertTrue($this->provider()->options('ENBR')->isEmpty());
    }

    #[Test]
    public function it_narrows_to_the_area_given_in_context(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();
        $inArea = Position::factory()->create(['area_id' => $area1->id, 'callsign' => 'ENGM_TWR']);
        Position::factory()->create(['area_id' => $area2->id, 'callsign' => 'ENGM_APP']);

        $this->actingAs($this->admin());

        $options = $this->provider()->options('ENGM', ['area' => $area1->id]);

        $this->assertCount(1, $options);
        $this->assertSame($inArea->callsign, $options->first()['value']);
    }

    #[Test]
    public function it_limits_the_number_of_returned_options(): void
    {
        foreach (range(1, 20) as $i) {
            Position::factory()->create(['callsign' => sprintf('QZZZ_%02d', $i)]);
        }

        $this->actingAs($this->admin());

        $this->assertSame(FeedbackPositionOptions::LIMIT, $this->provider()->options('QZZZ')->count());
    }
}
