<?php

namespace Tests\Feature;

use App\TrainingExamination;
use App\User;
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
        $this->examination = factory(TrainingExamination::class)->make();
        $this->training = $this->examination->training;
        $this->training->country->mentors()->attach($this->examination->examiner);
    }

    /** @test */
    public function examiner_can_access_examination_page()
    {
        $this->actingAs($this->examination->examiner)->get(route('training.examination.create', ['training' => $this->training]))
            ->assertStatus(200);
    }

    /** @test */
    public function student_cant_access_examination_page()
    {
        $this->actingAs($this->training->user)->get(route('training.examination.create', ['training' => $this->training]))
            ->assertStatus(403);
    }

    /** @test */
    public function examiner_can_store_examination()
    {

        $this->withoutExceptionHandling();

        $data = $this->examination->getAttributes();

        $this->actingAs($this->examination->examiner)->followingRedirects()
            ->postJson(route('training.examination.store', ['training' => $this->training]), $data)
            ->assertStatus(200)
            ->assertJson(['message' => 'Examination successfully added']);

        $this->assertDatabaseHas('training_examinations', [
            'training_id' => $data['training_id'],
            'examiner_id' => $data['examiner_id'],
            'position_id' => $data['position_id']
        ]);

    }

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
        $this->training->user->update(['group' => 2]);

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
    public function moderator_can_update_non_draft_examination()
    {

        $examination = factory(TrainingExamination::class)->create(['draft' => false]);
        $moderator = factory(User::class)->create(['group' => 2]);

        $this->actingAs($moderator)->followingRedirects()
            ->patchJson(route('training.examination.update', ['examination' => $examination]), ['result' => 'PASSED'])
            ->assertJson(['message' => 'Examination successfully updated'])
            ->assertStatus(200);

        $this->assertDatabaseHas('training_examinations', [
            'id' => $examination->id,
            'result' => 'PASSED'
        ]);

    }

    /** @test */
    public function examiner_cant_update_non_draft_examination()
    {

        $examination = factory(TrainingExamination::class)->create(['draft' => false]);
        $examination->examiner->update(['group' => 3]);

        $this->actingAs($examination->examiner)->followingRedirects()
            ->patchJson(route('training.examination.update', ['examination' => $examination]), ['result' => 'PASSED'])
            ->assertJsonMissing(['message'])
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_examinations', [
            'id' => $examination->id,
            'result' => 'PASSED'
        ]);

    }

    /** @test */
    public function examiner_can_update_draft_examination()
    {

        $examination = factory(TrainingExamination::class)->create(['draft' => true]);
        $examination->examiner->update(['group' => 3]);

        $this->actingAs($examination->examiner)->followingRedirects()
            ->patchJson(route('training.examination.update', ['examination' => $examination]), ['result' => 'PASSED'])
            ->assertJson(['message' => 'Examination successfully updated'])
            ->assertStatus(200);

        $this->assertDatabaseHas('training_examinations', [
            'id' => $examination->id,
            'result' => 'PASSED'
        ]);
    }

    /** @test */
    public function moderator_can_delete_training_examination()
    {

        $examination = factory(TrainingExamination::class)->create();
        $moderator = factory(User::class)->create(['group' => 2]);

        $this->actingAs($moderator)->followingRedirects()
            ->deleteJson(route('training.examination.delete', ['examination' => $examination]))
            ->assertJson(['message' => 'Examination successfully deleted'])
            ->assertStatus(200);

        $this->assertDatabaseMissing('training_examinations', ['id' => $examination->id]);

    }

    /** @test */
    public function mentor_cant_delete_training_examination()
    {

        $examination = factory(TrainingExamination::class)->create();
        $mentor = factory(User::class)->create(['group' => 3]);

        $this->actingAs($mentor)->followingRedirects()
            ->delete(route('training.examination.delete', ['examination' => $examination]))
            ->assertStatus(403);

        $this->assertDatabaseHas('training_examinations', ['id' => $examination->id]);

    }


}
