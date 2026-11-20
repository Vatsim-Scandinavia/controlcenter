<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeedbackSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('feedbackEnabled', true);
    }

    private function createActiveController(Area $area): User
    {
        $controller = User::factory()->create(['rating' => VatsimRating::S2->value]);

        AtcActivity::create([
            'user_id' => $controller->id,
            'area_id' => $area->id,
            'hours' => 10,
            'atc_active' => true,
        ]);

        return $controller;
    }

    #[Test]
    public function feedback_page_lists_areas_to_choose_from(): void
    {
        $areas = Area::factory()->count(2)->create();

        $response = $this->actingAs(User::factory()->create())->get(route('feedback'));

        $response->assertOk();
        $response->assertSee(route('feedback.create', $areas[0]));
        $response->assertSee(route('feedback.create', $areas[1]));
        $response->assertSee(route('feedback.create.general'));
    }

    #[Test]
    public function area_form_only_lists_positions_and_controllers_from_that_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();

        $position = Position::factory()->create(['area_id' => $area->id, 'callsign' => 'EKCH_TWR']);
        $otherPosition = Position::factory()->create(['area_id' => $otherArea->id, 'callsign' => 'ESSA_TWR']);
        $controller = $this->createActiveController($area);
        $otherController = $this->createActiveController($otherArea);

        $response = $this->actingAs(User::factory()->create())->get(route('feedback.create', $area));

        $response->assertOk();
        $response->assertViewHas('area', fn (?Area $viewArea): bool => $viewArea?->is($area));
        $response->assertViewHas('positions', fn ($positions): bool => $positions->contains($position) && ! $positions->contains($otherPosition));
        $response->assertViewHas('controllers', fn ($controllers): bool => $controllers->contains($controller) && ! $controllers->contains($otherController));
    }

    #[Test]
    public function general_form_lists_positions_and_controllers_from_all_areas(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();

        $position = Position::factory()->create(['area_id' => $area->id]);
        $otherPosition = Position::factory()->create(['area_id' => $otherArea->id]);
        $controller = $this->createActiveController($area);
        $otherController = $this->createActiveController($otherArea);

        $response = $this->actingAs(User::factory()->create())->get(route('feedback.create.general'));

        $response->assertOk();
        $response->assertViewHas('area', null);
        $response->assertViewHas('positions', fn ($positions): bool => $positions->contains($position) && $positions->contains($otherPosition));
        $response->assertViewHas('controllers', fn ($controllers): bool => $controllers->contains($controller) && $controllers->contains($otherController));
    }

    #[Test]
    public function unknown_area_returns_not_found(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('feedback.create', 999999));

        $response->assertNotFound();
    }

    #[Test]
    public function feedback_without_position_is_stored_with_the_selected_area(): void
    {
        $area = Area::factory()->create();

        $response = $this->actingAs(User::factory()->create())->post(route('feedback.store'), [
            'area' => $area->id,
            'feedback' => 'Great service.',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('feedback', [
            'area_id' => $area->id,
            'reference_position_id' => null,
        ]);
    }

    #[Test]
    public function feedback_with_position_takes_its_area_from_the_position(): void
    {
        $area = Area::factory()->create();
        $position = Position::factory()->create(['area_id' => $area->id]);

        $response = $this->actingAs(User::factory()->create())->post(route('feedback.store'), [
            'area' => $area->id,
            'position' => $position->callsign,
            'feedback' => 'Great service.',
        ]);

        $response->assertRedirect(route('dashboard'));
        $feedback = Feedback::sole();
        $this->assertNull($feedback->area_id);
        $this->assertSame($position->id, $feedback->reference_position_id);
        $this->assertTrue($feedback->area->is($area));
    }

    #[Test]
    public function general_feedback_is_stored_without_an_area(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('feedback.store'), [
            'area' => '',
            'feedback' => 'The website is great.',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('feedback', [
            'feedback' => 'The website is great.',
            'area_id' => null,
        ]);
    }

    #[Test]
    public function position_from_another_area_is_rejected(): void
    {
        $area = Area::factory()->create();
        $otherPosition = Position::factory()->create(['area_id' => Area::factory()->create()->id]);

        $response = $this->actingAs(User::factory()->create())->post(route('feedback.store'), [
            'area' => $area->id,
            'position' => $otherPosition->callsign,
            'feedback' => 'Wrong area.',
        ]);

        $response->assertSessionHasErrors('position');
        $this->assertSame(0, Feedback::count());
    }

    #[Test]
    public function unknown_area_is_rejected_on_submit(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('feedback.store'), [
            'area' => 999999,
            'feedback' => 'Nowhere.',
        ]);

        $response->assertSessionHasErrors('area');
        $this->assertSame(0, Feedback::count());
    }

    #[Test]
    public function area_picker_redirects_when_feedback_is_disabled(): void
    {
        Setting::set('feedbackEnabled', false);

        $response = $this->actingAs(User::factory()->create())->get(route('feedback'));

        $response->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function feedback_form_redirects_when_feedback_is_disabled(): void
    {
        Setting::set('feedbackEnabled', false);
        $area = Area::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get(route('feedback.create', $area));

        $response->assertRedirect(route('dashboard'));
    }
}
