<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrainingExaminationsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Training $training;

    private TrainingExamination $examination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->training = Training::factory()->create([
            'user_id' => User::factory()->create([
                'id' => 10000009,
            ])->id,
        ]);

        $examiner = User::factory()->create([
            'id' => 10000001,
        ]);

        $examinerEndorsement = Endorsement::factory()->create([
            'user_id' => $examiner->id,
            'type' => 'EXAMINER',
            'valid_from' => Carbon::now(),
        ]);

        $examinerEndorsement->ratings()->save(Rating::find(5));
        $examinerEndorsement->areas()->save(Area::find(1));

        $this->examination = TrainingExamination::factory()->make([
            'training_id' => $this->training->id,
            'examiner_id' => $examiner->id,
            'position_id' => 1,
            'examination_date' => Carbon::now(),
        ]);

        $examinerEndorsement = Endorsement::factory()->create([
            'user_id' => $this->examination->examiner->id,
            'type' => 'EXAMINER',
            'valid_from' => Carbon::now(),
        ]);
    }

    #[Test]
    public function student_cant_access_examination_page()
    {
        $this->actingAs($this->training->user)->get(route('training.examination.create', ['training' => $this->training]))
            ->assertStatus(403);
    }

    /** Test is broken due to notifications triggering when you create a new examination. This breaks typically on Github Workflow where notifications are not configured.  */
    /*
    #[Test]
    public function examiner_can_store_examination()
    {
        $data = $this->examination->getAttributes();

        // When we push json, the controller requests a bit different data
        $data['examination_date'] = Carbon::parse($data['examination_date'])->format('d/m/Y');
        $data['position'] = Position::find($data['position_id'])->callsign;

        $this->actingAs($this->examination->examiner)->followingRedirects()
            ->postJson(route('training.examination.store', ['training' => $this->training]), $data)
            ->assertStatus(200);

        $this->assertDatabaseHas('training_examinations', [
            'training_id' => $data['training_id'],
            'examiner_id' => $data['examiner_id'],
        ]);

    }*/

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

        $moderator = User::factory()->create(['id' => 10000004]);
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

        $mentor = User::factory()->create(['id' => 10000004]);
        $mentor->groups()->attach(3, ['area_id' => $this->training->area->id]);

        $this->actingAs($mentor)->followingRedirects()
            ->get(route('training.examination.delete', ['examination' => $examination]))
            ->assertStatus(403);

        $this->assertDatabaseHas('training_examinations', ['id' => $examination->id]);
    }
}
