<?php

namespace Tests\Feature;

use App\Livewire\FeedbackTable;
use App\Models\Area;
use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreatesRoleAssignedUsers;
use Tests\TestCase;

class FeedbackOverviewTest extends TestCase
{
    use CreatesRoleAssignedUsers;
    use RefreshDatabase;

    #[Test]
    public function guest_without_role_cannot_mount_the_component(): void
    {
        // Livewire's RequestBroker deliberately lets AuthorizationException (and
        // HttpException) through its original exception handler during testing
        // (see RequestBroker::temporarilyDisableExceptionHandlingAndMiddleware),
        // so the gate denial surfaces as a 403 response rather than a thrown
        // exception reaching the test.
        Livewire::actingAs(User::factory()->create())
            ->test(FeedbackTable::class)
            ->assertForbidden();
    }

    #[Test]
    public function admin_sees_all_correlated_and_uncorrelated_feedback(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();
        $p1 = Position::factory()->create(['area_id' => $area1->id]);
        $p2 = Position::factory()->create(['area_id' => $area2->id]);

        $a = Feedback::factory()->create(['reference_position_id' => $p1->id]);
        $b = Feedback::factory()->create(['reference_position_id' => $p2->id]);
        $c = Feedback::factory()->uncorrelated()->create();

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->assertViewHas('feedbacks', fn ($feedbacks) => $feedbacks->contains($a)
                && $feedbacks->contains($b)
                && $feedbacks->contains($c));
    }

    #[Test]
    public function moderator_sees_only_own_area_correlated_and_uncorrelated(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();
        $moderator = $this->moderatorFor($area1);

        $p1 = Position::factory()->create(['area_id' => $area1->id]);
        $p2 = Position::factory()->create(['area_id' => $area2->id]);

        $inArea = Feedback::factory()->create(['reference_position_id' => $p1->id]);
        $otherArea = Feedback::factory()->create(['reference_position_id' => $p2->id]);
        $uncorrelated = Feedback::factory()->uncorrelated()->create();

        Livewire::actingAs($moderator)
            ->test(FeedbackTable::class)
            ->assertViewHas('feedbacks', fn ($feedbacks) => $feedbacks->contains($inArea)
                && $feedbacks->contains($uncorrelated)
                && ! $feedbacks->contains($otherArea));
    }

    #[Test]
    public function area_name_is_shown_and_uncorrelated_shows_na(): void
    {
        $area = Area::factory()->create(['name' => 'Test Area']);
        $position = Position::factory()->create(['area_id' => $area->id]);
        Feedback::factory()->create(['reference_position_id' => $position->id]);
        Feedback::factory()->uncorrelated()->create();

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->assertSee('Test Area')
            ->assertSee('N/A');
    }

    #[Test]
    public function feedback_text_is_html_escaped_on_the_report(): void
    {
        $position = Position::factory()->create();
        Feedback::factory()->create([
            'reference_position_id' => $position->id,
            'feedback' => '<script>alert(1)</script>',
        ]);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    #[Test]
    public function report_renders_a_single_shared_edit_modal(): void
    {
        $position = Position::factory()->create();
        Feedback::factory()->count(3)->create(['reference_position_id' => $position->id]);

        $html = Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->html();

        $this->assertSame(1, substr_count($html, 'id="feedback-edit-modal"'));
    }

    #[Test]
    public function feedback_free_text_search_narrows_results(): void
    {
        $position = Position::factory()->create();
        $match = Feedback::factory()->create(['reference_position_id' => $position->id, 'feedback' => 'runway incursion near alpha']);
        $other = Feedback::factory()->create(['reference_position_id' => $position->id, 'feedback' => 'excellent handoff timing']);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->set('search', 'runway incursion')
            ->assertViewHas('feedbacks', fn ($f) => $f->contains($match) && ! $f->contains($other));
    }

    #[Test]
    public function controller_filter_is_free_text_and_narrows_by_name_or_id(): void
    {
        $position = Position::factory()->create();
        $ctrl = User::factory()->create(['first_name' => 'Zebediah', 'last_name' => 'Controller']);
        $match = Feedback::factory()->create(['reference_position_id' => $position->id, 'reference_user_id' => $ctrl->id]);
        $other = Feedback::factory()->create(['reference_position_id' => $position->id]);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            // free-text on (part of) the controller's name
            ->set('controller', 'Zebediah')
            ->assertViewHas('feedbacks', fn ($f) => $f->contains($match) && ! $f->contains($other))
            // spanning first and last name: the combobox sets the filter to the
            // full "First Last" string, so the match must survive across the space
            ->set('controller', 'Zebediah Controller')
            ->assertViewHas('feedbacks', fn ($f) => $f->contains($match) && ! $f->contains($other))
            // and by id, so the route('reports.feedback', ['controller' => id]) deep-link still works
            ->set('controller', (string) $ctrl->id)
            ->assertViewHas('feedbacks', fn ($f) => $f->contains($match) && ! $f->contains($other));
    }

    #[Test]
    public function clear_button_appears_only_when_a_filter_is_active(): void
    {
        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->assertDontSee('clearFilters')
            ->set('search', 'something')
            ->assertSee('clearFilters')
            ->set('search', '')
            ->assertDontSee('clearFilters');
    }

    #[Test]
    public function clear_filters_resets_every_filter_field(): void
    {
        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->set('search', 'a')
            ->set('controller', 'b')
            ->set('position', 'c')
            ->set('submitter', 'd')
            ->set('area', 5)
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('controller', '')
            ->assertSet('position', '')
            ->assertSet('submitter', '')
            ->assertSet('area', null);
    }

    #[Test]
    public function position_filter_is_free_text_and_narrows_by_callsign(): void
    {
        $p1 = Position::factory()->create(['callsign' => 'ENGM_TWR']);
        $p2 = Position::factory()->create(['callsign' => 'ENBR_APP']);
        $match = Feedback::factory()->create(['reference_position_id' => $p1->id]);
        $other = Feedback::factory()->create(['reference_position_id' => $p2->id]);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->set('position', 'ENGM')
            ->assertViewHas('feedbacks', fn ($f) => $f->contains($match) && ! $f->contains($other));
    }

    #[Test]
    public function area_filter_narrows_to_positions_in_that_area(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();
        $p1 = Position::factory()->create(['area_id' => $area1->id]);
        $p2 = Position::factory()->create(['area_id' => $area2->id]);
        $inArea = Feedback::factory()->create(['reference_position_id' => $p1->id]);
        $otherArea = Feedback::factory()->create(['reference_position_id' => $p2->id]);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->set('area', $area1->id)
            ->assertViewHas('feedbacks', fn ($f) => $f->contains($inArea) && ! $f->contains($otherArea));
    }

    #[Test]
    public function crafted_out_of_scope_position_filter_cannot_widen_scope(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();
        $moderator = $this->moderatorFor($area1);

        $outOfScopePosition = Position::factory()->create(['area_id' => $area2->id, 'callsign' => 'ENBR_APP']);
        $hidden = Feedback::factory()->create(['reference_position_id' => $outOfScopePosition->id]);

        Livewire::actingAs($moderator)
            ->test(FeedbackTable::class)
            // even free-text matching the out-of-scope position's callsign cannot surface it
            ->set('position', $outOfScopePosition->callsign)
            ->assertViewHas('feedbacks', fn ($f) => ! $f->contains($hidden) && $f->isEmpty());
    }

    #[Test]
    public function it_uses_lazy_comboboxes_instead_of_eager_datalists(): void
    {
        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            // the eager filter datalists are gone, replaced by lazy comboboxes
            ->assertDontSee('feedback-filter-controllers')
            ->assertDontSee('feedback-filter-positions')
            ->assertSee('role="combobox"', false);
    }

    #[Test]
    public function edit_modal_reference_options_are_deferred_until_the_modal_opens(): void
    {
        // A distinctive position that no feedback references, so it can only
        // appear in the edit modal's datalist, never in a table row.
        Position::factory()->create(['callsign' => 'ZZZZ_TWR', 'name' => 'Datalist Only']);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            // Nothing loaded on a plain render: the datalist is empty.
            ->assertSet('showReferenceOptions', false)
            ->assertDontSee('ZZZZ_TWR')
            // Opening the modal (front-end toggles this flag) loads the options.
            ->set('showReferenceOptions', true)
            ->assertSee('ZZZZ_TWR');
    }

    #[Test]
    public function submitter_search_matches_on_name_and_does_not_error(): void
    {
        // Regression: the submitter filter must query the real first_name/
        // last_name columns, not the `name` accessor. `where('name', ...)`
        // errors on MySQL and silently matches nothing on SQLite.
        $position = Position::factory()->create();

        $alice = User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Anderson']);
        $bob = User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Brown']);
        $fromAlice = Feedback::factory()->create(['reference_position_id' => $position->id, 'submitter_user_id' => $alice->id]);
        $fromBob = Feedback::factory()->create(['reference_position_id' => $position->id, 'submitter_user_id' => $bob->id]);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->set('submitter', 'Alice')
            ->assertViewHas('feedbacks', fn ($f) => $f->contains($fromAlice) && ! $f->contains($fromBob));
    }

    #[Test]
    public function per_page_limits_the_number_of_rows(): void
    {
        $position = Position::factory()->create();
        Feedback::factory()->count(30)->create(['reference_position_id' => $position->id]);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->assertViewHas('feedbacks', fn ($f) => $f->count() === 25)
            ->set('perPage', 50)
            ->assertViewHas('feedbacks', fn ($f) => $f->count() === 30);
    }

    #[Test]
    public function changing_a_filter_resets_to_the_first_page(): void
    {
        $position = Position::factory()->create();
        Feedback::factory()->count(60)->create(['reference_position_id' => $position->id]);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('search', 'x')
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function crafted_sort_direction_falls_back_to_desc_and_does_not_error(): void
    {
        $position = Position::factory()->create();
        $older = Feedback::factory()->create(['reference_position_id' => $position->id, 'created_at' => now()->subDays(2)]);
        $newer = Feedback::factory()->create(['reference_position_id' => $position->id, 'created_at' => now()]);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->set('sortDirection', 'foo')
            ->assertOk()
            ->assertViewHas('feedbacks', fn ($f) => $f->first()->is($newer) && $f->last()->is($older));
    }

    #[Test]
    public function crafted_per_page_falls_back_to_default(): void
    {
        $position = Position::factory()->create();
        Feedback::factory()->count(30)->create(['reference_position_id' => $position->id]);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->set('perPage', 999999)
            ->assertViewHas('feedbacks', fn ($f) => $f->count() === 25);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->set('perPage', 0)
            ->assertViewHas('feedbacks', fn ($f) => $f->count() === 25);
    }

    #[Test]
    public function received_sort_can_toggle_to_ascending(): void
    {
        $position = Position::factory()->create();
        $older = Feedback::factory()->create(['reference_position_id' => $position->id, 'created_at' => now()->subDays(2)]);
        $newer = Feedback::factory()->create(['reference_position_id' => $position->id, 'created_at' => now()]);

        Livewire::actingAs($this->admin())
            ->test(FeedbackTable::class)
            ->assertViewHas('feedbacks', fn ($f) => $f->first()->is($newer))
            ->call('sortByReceived')
            ->assertViewHas('feedbacks', fn ($f) => $f->first()->is($older));
    }
}
