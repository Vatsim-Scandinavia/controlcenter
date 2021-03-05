<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TrainingExaminationsTest extends TestCase
{

    use WithFaker, RefreshDatabase;

    private $examination, $training;

    protected function setUp(): void
    {
        parent::setUp();
        $this->examination = TrainingExamination::factory()->make([
            'training_id' => Training::factory()->create([
                'user_id' => User::factory()->create(['id' => 10000005])->id,
            ])->id,
            'examiner_id' => User::factory()->create([
                'id' => 10000001,
            ]),
        ]);

        $this->examination->examiner->groups()->attach(3, ['area_id' => $this->examination->training->area]);

        $this->training = $this->examination->training;
        $this->training->mentors()->attach($this->examination->examiner, ['expire_at' => now()->addMonths(12)]);
    }

    /** @test */
    public function student_cant_access_examination_page()
    {
        $this->actingAs($this->training->user)->get(route('training.examination.create', ['training' => $this->training]))
            ->assertStatus(403);
    }

    // TODO Fix this test as it's breaking now with the notifications
//    /** @test */
//    public function examiner_can_store_examination()
//    {
//
//        $data = $this->examination->getAttributes();
//        $data['examination_date'] = Carbon::parse($data['examination_date'])->format('d/m/Y');
//        $data['position'] = \App\Models\Position::find($data['position_id'])->callsign;
//        unset($data['position_id']);
//
//        $this->actingAs($this->examination->examiner)->followingRedirects()
//            ->postJson(route('training.examination.store', ['training' => $this->training]), $data)
//            ->assertStatus(200)
//            ->assertJson(['message' => 'Examination successfully added']);
//
//        $this->assertDatabaseHas('training_examinations', [
//            'training_id' => $data['training_id'],
//            'examiner_id' => $data['examiner_id']
//        ]);
//
//    }

    /** @test */
    public function student_cant_store_examination()
    {

        $data = $this->examination->getAttributes();
        $this->actingAs($this->training->user)->followingRedirects()
            ->postJson(route('training.examination.store', ['training' => $this->training]), $data)
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_examinations', [
            'training_id' => $data['training_id'],
            'examiner_id' => $data['examiner_id'],
            'position_id' => $data['position_id']
        ]);

    }

    /** @test */
    public function student_cant_store_examination_even_though_they_are_an_examiner()
    {

        $data = $this->examination->getAttributes();
        $this->training->user->groups()->detach();
        $this->training->user->groups()->attach(3, ['area_id' => $this->training->area->id]);

        $this->actingAs($this->training->user)->followingRedirects()
            ->postJson(route('training.examination.store', ['training' => $this->training]), $data)
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_examinations', [
            'training_id' => $data['training_id'],
            'examiner_id' => $data['examiner_id'],
            'position_id' => $data['position_id']
        ]);

    }

//    /** @test */
//    public function moderator_can_update_non_draft_examination()
//    {
//
//        $examination = factory(TrainingExamination::class)->create(['draft' => false]);
//        $moderator = factory(User::class)->create(['group' => 2]);
//
//        $this->actingAs($moderator)->followingRedirects()
//            ->patchJson(route('training.examination.update', ['examination' => $examination]), ['result' => 'PASSED'])
//            ->assertJson(['message' => 'Examination successfully updated'])
//            ->assertStatus(200);
//
//        $this->assertDatabaseHas('training_examinations', [
//            'id' => $examination->id,
//            'result' => 'PASSED'
//        ]);
//
//    }
//
//    /** @test */
//    public function examiner_cant_update_non_draft_examination()
//    {
//
//        $examination = factory(TrainingExamination::class)->create(['draft' => false]);
//        $examination->examiner->update(['group' => 3]);
//
//        $this->actingAs($examination->examiner)->followingRedirects()
//            ->patchJson(route('training.examination.update', ['examination' => $examination]), ['result' => 'PASSED'])
//            ->assertJsonMissing(['message'])
//            ->assertStatus(403);
//
//        $this->assertDatabaseMissing('training_examinations', [
//            'id' => $examination->id,
//            'result' => 'PASSED'
//        ]);
//
//    }
//
//    /** @test */
//    public function examiner_can_update_draft_examination()
//    {
//
//        $examination = factory(TrainingExamination::class)->create(['draft' => true]);
//        $examination->examiner->update(['group' => 3]);
//
//        $this->actingAs($examination->examiner)->followingRedirects()
//            ->patchJson(route('training.examination.update', ['examination' => $examination]), ['result' => 'PASSED'])
//            ->assertJson(['message' => 'Examination successfully updated'])
//            ->assertStatus(200);
//
//        $this->assertDatabaseHas('training_examinations', [
//            'id' => $examination->id,
//            'result' => 'PASSED'
//        ]);
//    }

    /** @test */
    public function moderator_can_delete_training_examination()
    {

        $examination = TrainingExamination::factory()->create([
            'training_id' => Training::factory()->create([
                'user_id' => User::factory()->create(['id' => 10000009])->id,
            ])->id,
            'examiner_id' => User::factory()->create([
                'id' => 10000007,
            ])->id,
        ]);

        $examination->examiner->groups()->attach(3, ['area_id' => $examination->training->area->id]);

        $moderator = User::factory()->create(['id' => 10000004]);
        $moderator->groups()->attach(2, ['area_id' => $examination->training->area->id]);

        $this->actingAs($moderator)->followingRedirects()
            ->deleteJson(route('training.examination.delete', ['examination' => $examination]))
            ->assertJson(['message' => 'Examination successfully deleted'])
            ->assertStatus(200);

        $this->assertDatabaseMissing('training_examinations', ['id' => $examination->id]);

    }

    /** @test */
    public function mentor_cant_delete_training_examination()
    {

        $examination = TrainingExamination::factory()->create([
            'training_id' => Training::factory()->create([
                'user_id' => User::factory()->create(['id' => 10000035])->id,
            ])->id,
        ]);
        $mentor = User::factory()->create(['id' => 10000010]);
        $mentor->groups()->attach(3, ['area_id' => $examination->training->area->id]);

        $this->actingAs($mentor)->followingRedirects()
            ->delete(route('training.examination.delete', ['examination' => $examination]))
            ->assertStatus(403);

        $this->assertDatabaseHas('training_examinations', ['id' => $examination->id]);

    }


}
