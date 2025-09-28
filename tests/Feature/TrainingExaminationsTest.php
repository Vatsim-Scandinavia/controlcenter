<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Endorsement;
use App\Models\OneTimeLink;
use App\Models\Position;
use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrainingExaminationsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Training $training;

    private TrainingExamination $examination;

    private User $examiner;

    private OneTimeLink $oneTimeLink;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // Create area for training
        $area = Area::factory()->create();

        // Create training with AWAITING_EXAM status for one-time link tests
        $this->training = Training::factory()->create([
            'user_id' => User::factory()->create()->id,
            'area_id' => $area->id,
            'status' => 3, // AWAITING_EXAM
        ]);

        // Create examiner with endorsement
        $this->examiner = User::factory()->create();

        $examinerEndorsement = Endorsement::factory()->create([
            'user_id' => $this->examiner->id,
            'type' => 'EXAMINER',
            'valid_from' => Carbon::now(),
            'expired' => false,
            'revoked' => false,
        ]);

        $examinerEndorsement->ratings()->save(Rating::find(5));
        $examinerEndorsement->areas()->save($area);

        // Create examination for testing
        $this->examination = TrainingExamination::factory()->make([
            'training_id' => $this->training->id,
            'examiner_id' => $this->examiner->id,
            'position_id' => 1,
            'examination_date' => Carbon::now(),
        ]);

        // Create one-time link for testing
        $this->oneTimeLink = OneTimeLink::create([
            'training_id' => $this->training->id,
            'training_object_type' => OneTimeLink::TRAINING_EXAMINATION_TYPE,
            'key' => 'test-onetime-key-123',
            'expires_at' => now()->addDays(7),
        ]);
    }

    #[Test]
    public function student_cant_access_examination_page()
    {
        $this->actingAs($this->training->user)->get(route('training.examination.create', ['training' => $this->training]))
            ->assertStatus(403);
    }

    #[Test]
    public function student_cant_store_examination()
    {
        $data = $this->examination->getAttributes();
        $this->actingAs($this->training->user)->followingRedirects()
            ->postJson(route('training.examination.store', ['training' => $this->training]), $data)
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_examinations', [
            'training_id' => $data['training_id'],
            'examiner_id' => $data['examiner_id'],
            'position_id' => $data['position_id'],
        ]);
    }

    #[Test]
    public function student_cant_store_examination_even_though_they_are_an_examiner()
    {
        $data = $this->examination->getAttributes();

        $student = $this->training->user;

        $examinerEndorsement = Endorsement::factory()->create([
            'user_id' => $student->id,
            'type' => 'EXAMINER',
            'valid_from' => Carbon::now(),
        ]);

        $examinerEndorsement->ratings()->save(Rating::find(5));
        $examinerEndorsement->areas()->save(Area::find(1));

        $this->actingAs($this->training->user)->followingRedirects()
            ->postJson(route('training.examination.store', ['training' => $this->training]), $data)
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_examinations', [
            'training_id' => $data['training_id'],
            'examiner_id' => $data['examiner_id'],
            'position_id' => $data['position_id'],
        ]);
    }

    #[Test]
    public function moderator_can_delete_training_examination()
    {

        $examination = TrainingExamination::create($this->examination->getAttributes());

        $moderator = User::factory()->create();
        $moderator->groups()->attach(2, ['area_id' => $this->training->area->id]);

        $this->actingAs($moderator)->followingRedirects()
            ->getJson(route('training.examination.delete', ['examination' => $examination]))
            ->assertJson(['message' => 'Examination successfully deleted'])
            ->assertStatus(200);

        $this->assertDatabaseMissing('training_examinations', ['id' => $examination->id]);
    }

    #[Test]
    public function mentor_cant_delete_training_examination()
    {

        $examination = TrainingExamination::create($this->examination->getAttributes());

        $mentor = User::factory()->create();
        $mentor->groups()->attach(3, ['area_id' => $this->training->area->id]);

        $this->actingAs($mentor)->followingRedirects()
            ->get(route('training.examination.delete', ['examination' => $examination]))
            ->assertStatus(403);

        $this->assertDatabaseHas('training_examinations', ['id' => $examination->id]);
    }

    #[Test]
    public function examiner_can_store_examination()
    {
        $data = $this->examination->getAttributes();

        // When we push json, the controller requests a bit different data
        $data['examination_date'] = Carbon::now()->format('d/m/Y');
        $data['position'] = Position::find($data['position_id'])->callsign;

        $this->actingAs($this->examination->examiner)->followingRedirects()
            ->postJson(route('training.examination.store', ['training' => $this->training]), $data)
            ->assertStatus(200);

        $this->assertDatabaseHas('training_examinations', [
            'training_id' => $data['training_id'],
            'examiner_id' => $data['examiner_id'],
        ]);
    }

    #[Test]
    public function examiner_without_one_time_link_cannot_view_training_directly()
    {
        $response = $this->actingAs($this->examiner)
            ->get(route('training.show', $this->training->id));

        $response->assertStatus(403);
    }

    #[Test]
    public function examiner_with_one_time_link_can_successfully_create_exam_results()
    {
        $this->assertTrue($this->examiner->isExaminer($this->training->area), 'Examiner should have examiner endorsement for the training area');
        $this->assertTrue($this->examiner->isNot($this->training->user), 'Examiner should not be the training owner');

        $response = $this->actingAs($this->examiner)
            ->followingRedirects()
            ->get(route('training.onetimelink.redirect', ['key' => $this->oneTimeLink->key]));

        $response->assertStatus(200);
        $response->assertViewIs('training.exam.create');

        $this->assertTrue(session()->has('onetimekey'));
        $this->assertEquals($this->oneTimeLink->key, session()->get('onetimekey'));

        $examData = [
            'position' => Position::find(1)->callsign,
            'result' => 'PASSED',
            'examination_date' => Carbon::now()->format('d/m/Y'),
        ];

        $response = $this->actingAs($this->examiner)
            ->post(route('training.examination.store', ['training' => $this->training]), $examData);

        $this->assertDatabaseHas('training_examinations', [
            'training_id' => $this->training->id,
            'examiner_id' => $this->examiner->id,
            'result' => 'PASSED',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success', 'Examination successfully added');

        $this->assertDatabaseMissing('one_time_links', [
            'key' => $this->oneTimeLink->key,
        ]);

        $this->assertFalse(session()->has('onetimekey'));
    }

    #[Test]
    public function examiner_with_one_time_link_can_retry_after_invalid_data_submission_without_losing_session()
    {
        $response = $this->actingAs($this->examiner)
            ->get(route('training.onetimelink.redirect', ['key' => $this->oneTimeLink->key]));

        $response->assertRedirect(route('training.examination.create', ['training' => $this->training]));
        $this->assertEquals($this->oneTimeLink->key, session()->get('onetimekey'));

        $invalidExamData = [
            'position' => '', // Invalid: empty position
            'result' => 'INVALID_RESULT', // Invalid: not in allowed values
            'examination_date' => 'invalid-date', // Invalid: wrong format
        ];

        $response = $this->actingAs($this->examiner)
            ->post(route('training.examination.store', ['training' => $this->training]), $invalidExamData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['position', 'result', 'examination_date']);

        $this->assertTrue(session()->has('onetimekey'), 'Session variable should persist after validation failure');
        $this->assertEquals('test-onetime-key-123', session()->get('onetimekey'));

        $response = $this->actingAs($this->examiner)
            ->get(route('training.examination.create', ['training' => $this->training]));
        $response->assertStatus(200);

        $validExamData = [
            'position' => Position::find(1)->callsign,
            'result' => 'PASSED',
            'examination_date' => Carbon::now()->format('d/m/Y'),
        ];

        $response = $this->actingAs($this->examiner)
            ->post(route('training.examination.store', ['training' => $this->training]), $validExamData);

        $this->assertDatabaseHas('training_examinations', [
            'training_id' => $this->training->id,
            'examiner_id' => $this->examiner->id,
            'result' => 'PASSED',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Examination successfully added');

        $this->assertDatabaseMissing('one_time_links', [
            'key' => $this->oneTimeLink->key,
        ]);

        $this->assertFalse(session()->has('onetimekey'));
    }

    /**
     * Tests the bug scenario where session loss causes incorrect redirect behavior.
     *
     * This test demonstrates a specific bug where losing the session variable after
     * accessing the one-time link, before publishing the results, causes examiners to
     * be redirected to the training view instead of the dashboard.
     *
     * The fixed version instead redirects to the dashboard if the user does not
     * have access to view the training.
     */
    #[Test]
    public function examiner_with_one_time_link_gets_nice_redirect_when_session_variable_is_lost_before_publishing()
    {
        $response = $this->actingAs($this->examiner)
            ->get(route('training.onetimelink.redirect', ['key' => $this->oneTimeLink->key]));

        $response->assertRedirect(route('training.examination.create', ['training' => $this->training]));
        $this->assertEquals($this->oneTimeLink->key, session()->get('onetimekey'));

        session()->forget('onetimekey');

        $examData = [
            'position' => Position::find(1)->callsign,
            'result' => 'PASSED',
            'examination_date' => Carbon::now()->format('d/m/Y'),
        ];

        $response = $this->actingAs($this->examiner)
            ->post(route('training.examination.store', ['training' => $this->training]), $examData);

        $this->assertDatabaseHas('training_examinations', [
            'training_id' => $this->training->id,
            'examiner_id' => $this->examiner->id,
            'result' => 'PASSED',
        ]);

        $response->assertRedirect(route('dashboard'));
        $finalResponse = $this->actingAs($this->examiner)
            ->followingRedirects()
            ->get(route('training.show', $this->training->id));

        $finalResponse->assertStatus(403);
    }

    #[Test]
    public function examiner_with_one_time_link_can_access_exam_creation()
    {
        $response = $this->actingAs($this->examiner)
            ->get(route('training.onetimelink.redirect', ['key' => $this->oneTimeLink->key]));

        $response->assertRedirect(route('training.examination.create', ['training' => $this->training]));

        $response->assertSessionHas('onetimekey', $this->oneTimeLink->key);
    }

    #[Test]
    public function examiner_with_expired_one_time_link_is_rejected()
    {
        $this->oneTimeLink->update([
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($this->oneTimeLink->expires_at < now(), 'One-time link should be expired');

        $response = $this->actingAs($this->examiner)
            ->get(route('training.onetimelink.redirect', ['key' => $this->oneTimeLink->key]));

        $response->assertStatus(404);
        $this->assertFalse(session()->has('onetimekey'), 'Session variable should not be set for expired link');
    }

    #[Test]
    public function examiner_for_area_without_one_time_link_for_area_can_access_exam_creation()
    {
        $response = $this->actingAs($this->examiner)
            ->get(route('training.examination.create', ['training' => $this->training]));

        $response->assertStatus(200);
    }

    #[Test]
    public function examiner_without_one_time_link_from_different_area_cannot_access_exam_creation()
    {
        // Create examiner with endorsement for different area
        $differentArea = Area::factory()->create();
        $examinerForDifferentArea = User::factory()->create();
        $endorsement = Endorsement::factory()->create([
            'user_id' => $examinerForDifferentArea->id,
            'type' => 'EXAMINER',
            'valid_from' => Carbon::now(),
            'expired' => false,
            'revoked' => false,
        ]);
        $endorsement->areas()->save($differentArea);

        $response = $this->actingAs($examinerForDifferentArea)
            ->get(route('training.examination.create', ['training' => $this->training]));

        $response->assertStatus(403);
    }
}
