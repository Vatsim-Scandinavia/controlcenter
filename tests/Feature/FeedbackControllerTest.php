<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\Area;
use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use App\Notifications\FeedbackNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    #[Test]
    public function feedback_stores_the_pilot_callsign_uppercased_with_the_event_date(): void
    {
        $user = User::factory()->create();
        Setting::set('feedbackEnabled', true);

        $this->actingAs($user)->post(route('feedback.store'), [
            'feedback' => 'Great service',
            'pilot_callsign' => ' csa123 ',
            'event_date' => '2026-10-27',
            'departure_airport' => 'lkpr',
            'arrival_airport' => 'EGLL',
        ])->assertRedirect(route('dashboard'));

        $feedback = Feedback::where('submitter_user_id', $user->id)->sole();

        $this->assertSame('CSA123', $feedback->pilot_callsign);
        $this->assertSame('2026-10-27', $feedback->event_date->toDateString());
        $this->assertSame('LKPR', $feedback->departure_airport);
        $this->assertSame('EGLL', $feedback->arrival_airport);
    }

    #[Test]
    public function feedback_is_rejected_with_an_invalid_airport(): void
    {
        $user = User::factory()->create();

        foreach (['LK', 'PRG', 'LKPRX', 'LK-R'] as $airport) {
            $this->actingAs($user)->post(route('feedback.store'), [
                'feedback' => 'Great service',
                'departure_airport' => $airport,
                'arrival_airport' => $airport,
            ])->assertSessionHasErrors(['departure_airport', 'arrival_airport']);
        }
    }

    #[Test]
    public function pilot_callsign_and_event_date_are_optional(): void
    {
        $user = User::factory()->create();
        Setting::set('feedbackEnabled', true);

        $this->actingAs($user)->post(route('feedback.store'), [
            'feedback' => 'Great service',
            'pilot_callsign' => '',
            'event_date' => '',
        ])->assertSessionHasNoErrors();

        $feedback = Feedback::where('submitter_user_id', $user->id)->sole();

        $this->assertNull($feedback->pilot_callsign);
        $this->assertNull($feedback->event_date);
    }

    #[Test]
    public function feedback_is_rejected_with_an_invalid_pilot_callsign(): void
    {
        $user = User::factory()->create();

        foreach (['CSA 123', '<b>CSA</b>', str_repeat('A', 21)] as $callsign) {
            $this->actingAs($user)->post(route('feedback.store'), [
                'feedback' => 'Great service',
                'pilot_callsign' => $callsign,
            ])->assertSessionHasErrors('pilot_callsign');
        }
    }

    #[Test]
    public function feedback_is_rejected_with_an_event_date_in_the_future_or_of_the_wrong_format(): void
    {
        $user = User::factory()->create();

        foreach ([now()->addDay()->toDateString(), '27.10.2026'] as $eventDate) {
            $this->actingAs($user)->post(route('feedback.store'), [
                'feedback' => 'Great service',
                'event_date' => $eventDate,
            ])->assertSessionHasErrors('event_date');
        }
    }

    #[Test]
    public function feedback_is_rejected_with_an_impossible_event_date(): void
    {
        $this->actingAs(User::factory()->create())->post(route('feedback.store'), [
            'feedback' => 'Great service',
            'event_date' => '2026-02-30',
        ])->assertSessionHasErrors('event_date');
    }

    #[Test]
    public function feedback_accepts_a_pilot_callsign_of_the_maximum_length(): void
    {
        Setting::set('feedbackEnabled', true);

        $this->actingAs(User::factory()->create())->post(route('feedback.store'), [
            'feedback' => 'Great service',
            'pilot_callsign' => str_repeat('A', 20),
        ])->assertSessionHasNoErrors();
    }

    #[Test]
    public function feedback_accepts_today_as_the_event_date(): void
    {
        $user = User::factory()->create();
        Setting::set('feedbackEnabled', true);

        $this->actingAs($user)->post(route('feedback.store'), [
            'feedback' => 'Great service',
            'event_date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();
    }

    #[Test]
    public function feedback_form_offers_the_members_recent_flights(): void
    {
        $user = User::factory()->create();
        Setting::set('feedbackEnabled', true);
        $this->travelTo('2026-10-31 12:00:00');

        Http::fake([
            'api.vatsim.net/v2/members/' . $user->id . '/history*' => Http::response(['items' => [
                ['id' => 11, 'type' => 1, 'callsign' => 'CSA123', 'start' => '2026-10-27T17:47:58Z'],
            ]]),
            'api.vatsim.net/v2/members/' . $user->id . '/flightplans' => Http::response([
                ['connection_id' => 11, 'dep' => 'LKPR', 'arr' => 'EGLL'],
            ]),
        ]);

        $this->actingAs($user)->get(route('feedback'))
            ->assertOk()
            ->assertSee('id="recentFlight"', false)
            ->assertSee('data-date="2026-10-27"', false)
            ->assertSee('data-departure="LKPR"', false)
            ->assertSee('data-arrival="EGLL"', false)
            ->assertSee('LKPR');
    }

    #[Test]
    public function feedback_form_shows_atc_then_pilot_details_then_the_text(): void
    {
        $user = User::factory()->create();
        Setting::set('feedbackEnabled', true);

        Http::fake(['api.vatsim.net/*' => Http::response([], 503)]);

        $this->actingAs($user)->get(route('feedback'))->assertSeeHtmlInOrder([
            'name="controller"',
            'name="position"',
            'Pilot callsign',
            'Date of event, UTC',
            'Departure airport, ICAO',
            'Arrival airport, ICAO',
            'Your feedback',
        ]);
    }

    #[Test]
    public function feedback_form_does_not_call_the_api_when_feedback_is_disabled(): void
    {
        Setting::set('feedbackEnabled', false);
        Http::fake();

        $this->actingAs(User::factory()->create())->get(route('feedback'))->assertRedirect(route('dashboard'));

        Http::assertNothingSent();
    }

    #[Test]
    public function feedback_form_repopulates_flight_details_after_a_validation_error(): void
    {
        $user = User::factory()->create();
        Setting::set('feedbackEnabled', true);

        Http::fake(['api.vatsim.net/*' => Http::response([], 503)]);

        $this->actingAs($user)->from(route('feedback'))->followingRedirects()->post(route('feedback.store'), [
            'pilot_callsign' => 'CSA123',
            'departure_airport' => 'LKPR',
        ])->assertSee('value="CSA123"', false)->assertSee('value="LKPR"', false);
    }

    #[Test]
    public function feedback_form_still_works_when_the_flight_list_is_unavailable(): void
    {
        $user = User::factory()->create();
        Setting::set('feedbackEnabled', true);

        Http::fake(['api.vatsim.net/*' => Http::response([], 503)]);

        $this->actingAs($user)->get(route('feedback'))
            ->assertOk()
            ->assertDontSee('id="recentFlight"', false)
            ->assertSee('name="pilot_callsign"', false)
            ->assertSee('name="event_date"', false);
    }

    #[Test]
    public function updating_references_keeps_the_pilot_callsign_and_event_date(): void
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $position = Position::factory()->create(['area_id' => $area->id]);
        $feedback = Feedback::factory()->create([
            'reference_position_id' => $position->id,
            'pilot_callsign' => 'CSA123',
            'event_date' => '2026-10-27',
            'departure_airport' => 'LKPR',
        ]);

        $this->actingAs($moderator)->patch(route('feedback.update', $feedback), [
            'controller' => '',
            'position' => '',
            'pilot_callsign' => 'HACKED',
            'departure_airport' => 'HACK',
        ])->assertRedirect(route('reports.feedback'));

        $feedback->refresh();

        $this->assertSame('CSA123', $feedback->pilot_callsign);
        $this->assertSame('2026-10-27', $feedback->event_date->toDateString());
        $this->assertSame('LKPR', $feedback->departure_airport);
    }

    #[Test]
    public function forwarded_feedback_mail_shows_na_for_missing_flight_details(): void
    {
        Setting::set('feedbackEnabled', true);
        Setting::set('feedbackForwardEmail', 'staff@example.com');

        $feedback = Feedback::factory()->create();

        $html = (new FeedbackNotification($feedback))->toMail($feedback)->render();

        $this->assertStringContainsString('N/A', $html);
    }

    #[Test]
    public function forwarded_feedback_mail_contains_the_pilot_callsign_and_event_date(): void
    {
        Setting::set('feedbackEnabled', true);
        Setting::set('feedbackForwardEmail', 'staff@example.com');

        $feedback = Feedback::factory()->create([
            'pilot_callsign' => 'CSA123',
            'event_date' => '2026-10-27',
            'departure_airport' => 'LKPR',
            'arrival_airport' => 'EGLL',
        ]);

        $html = (new FeedbackNotification($feedback))->toMail($feedback)->render();

        $this->assertStringContainsString('CSA123', $html);
        $this->assertStringContainsString($feedback->event_date->toEuropeanDate(), $html);
        $this->assertStringContainsString('LKPR', $html);
        $this->assertStringContainsString('EGLL', $html);
    }
}
