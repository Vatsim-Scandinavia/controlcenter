<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TrainingReportsTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /** @test */
    public function mentor_can_access_training_reports()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000005])->id,
        ]);
        $mentor = factory(\App\User::class)->create(['group' => 3]);
        $training->country->mentors()->attach($mentor);
        $training->mentors()->attach($mentor, ['expire_at' => now()->addCentury()]);

        $this->actingAs($mentor)->assertTrue(Gate::inspect('viewReports', $training)->allowed());
    }

    /** @test */
    public function trainee_can_access_training_reports()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000005])->id,
        ]);
        $this->actingAs($training->user)->assertTrue(Gate::inspect('viewReports', $training)->allowed());
    }

    /** @test */
    public function a_regular_user_cant_access_training_reports()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000005])->id,
        ]);
        $otherUser = factory(\App\User::class)->create(['group' => null]);
        $this->actingAs($otherUser)->assertTrue(Gate::inspect('viewReports', $training)->denied());
    }

    /** @test */
    public function trainee_cant_access_draft_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000067, 'group' => null])->id,
        ]);
        $report = factory(\App\TrainingReport::class)->create(['draft' => true, 'training_id' => $training->id]);
        $this->actingAs($report->training->user)->assertTrue(Gate::inspect('view', $report)->denied());
    }

    /** @test */
    public function mentor_can_access_draft_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000042])->id,
        ]);
        $report = factory(\App\TrainingReport::class)->create(['draft' => true, 'training_id' => $training->id]);

        $mentor = factory(User::class)->create(['id' => 10000080, 'group' => 3]);
        $report->training->country->mentors()->attach($mentor);
        $training->mentors()->attach($mentor, ['expire_at' => now()->addYear()]);
        $this->actingAs($mentor)->assertTrue(Gate::inspect('view', $report)->allowed());
    }

//    /** @test */
//    public function mentor_can_create_training_report()
//    {
//        $report = factory(\App\TrainingReport::class)->make();
//
//        $this->actingAs($report->training->mentors()->first())
//            ->post(route('training.report.store', ['training' => $report->training->id]), $report->getAttributes())
//            ->assertStatus(302);
//
//        $this->assertDatabaseHas('training_reports', $report->getAttributes());
//    }

    /** @test */
    public function a_regular_user_cant_create_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000090])->id,
        ]);
        $report = factory(\App\TrainingReport::class)->make([
            'training_id' => $training->id,
        ]);

        $this->actingAs(factory(\App\User::class)->create(['group' => null]))
            ->post(route('training.report.store', ['training' => $report->training->id]), $report->getAttributes())
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_reports', $report->getAttributes());
    }

    /** @test */
    public function mentor_can_update_a_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000091])->id,
        ]);
        $report = factory(\App\TrainingReport::class)->create([
            'training_id' => $training->id,
        ]);
        $mentor = factory(User::class)->create(['id' => 10000015, 'group' => 3]);
        $content = $this->faker->paragraph();

        $report->training->country->mentors()->attach($mentor);
        $training->mentors()->attach($mentor, ['expire_at' => now()->addYear()]);

        $response = $this->actingAs($mentor)
            ->patch(route('training.report.update', ['report' => $report->id]), ['report_date' => today()->format('d/m/Y'), 'content' => $content])
            ->assertRedirect();

        $this->assertDatabaseHas('training_reports', ['content' => $content]);

    }

    /** @test */
    public function a_regular_user_cant_update_a_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000092])->id,
        ]);
        $report = factory(\App\TrainingReport::class)->create([
            'training_id' => $training->id,
        ]);
        $content = $this->faker->paragraph();

        $this->actingAs($report->training->user)
            ->patch(route('training.report.update', ['report' => $report->id]), ['content' => $content])
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_reports', ['content' => $content]);
    }

    /** @test */
    public function mentor_can_delete_a_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000093])->id,
            'id' => 2,
        ]);
        $report = factory(\App\TrainingReport::class)->create([
            'training_id' => $training->id,
            'report_date' => now()->addYear(),
            'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc quam nulla, rutrum sed massa et, ornare condimentum dolor. Vivamus massa ipsum, tincidunt eget leo a, lacinia euismod elit. Sed sodales placerat sem, vel euismod neque pellentesque vitae. In id ultrices leo. Sed ut nibh facilisis, mollis arcu at, egestas risus. Vestibulum dolor metus, iaculis a condimentum sed, rhoncus eget urna. Mauris lacinia facilisis massa nec porta.',

        ]);
        $mentor = factory(User::class)->create(['id' => 10000016, 'group' => 3]);

        $report->training->country->mentors()->attach($mentor);

        $this->actingAs($mentor)
            ->delete(route('training.report.delete', ['report' => $report->id]));

        $this->assertDatabaseMissing('training_reports', $report->getAttributes());
    }

    /** @test */
    public function another_mentor_cant_delete_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000094])->id,
        ]);
        $report = factory(\App\TrainingReport::class)->create([
            'training_id' => $training->id,
        ]);
        $otherMentor = factory(\App\User::class)->create(['id' => 10000100, 'group' => 3]);

        $this->actingAs($otherMentor)
            ->delete(route('training.report.delete', ['report' => $report->id]))
            ->assertStatus(403);

        $this->assertDatabaseHas('training_reports', $report->getAttributes());
    }

    /** @test */
    public function regular_user_cant_delete_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000095])->id,
        ]);
        $report = factory(\App\TrainingReport::class)->create([
            'training_id' => $training->id,
        ]);
        $regularUser = factory(\App\User::class)->create(['id' => 1000096, 'group' => null]);

        $this->actingAs($regularUser)
            ->delete(route('training.report.delete', ['report' => $report->id]))
            ->assertStatus(403);

        $this->assertDatabaseHas('training_reports', $report->getAttributes());
    }

    /** @test */
    public function another_moderator_can_delete_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000098])->id,
        ]);
        $report = factory(\App\TrainingReport::class)->create([
            'training_id' => $training->id,
        ]);
        $otherModerator = factory(\App\User::class)->create(['group' => 1, 'id' => 10000101]);

        $this->actingAs($otherModerator)
            ->delete(route('training.report.delete', ['report' => $report->id]));

        $this->assertDatabaseMissing('training_reports', $report->getAttributes());
    }


}
