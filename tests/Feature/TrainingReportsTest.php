<?php

namespace Tests\Feature;

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
        $training = factory(\App\Training::class)->create();
        $mentor = factory(\App\User::class)->create(['group' => 3]);
        $training->mentors()->attach($mentor, ['expire_at' => now()->addCentury()]);

        $this->actingAs($mentor)->assertTrue(Gate::inspect('viewReports', $training)->allowed());
    }

    /** @test */
    public function trainee_can_access_training_reports()
    {
        $training = factory(\App\Training::class)->create();
        $this->actingAs($training->user)->assertTrue(Gate::inspect('viewReports', $training)->allowed());
    }

    /** @test */
    public function a_regular_user_cant_access_training_reports()
    {
        $training = factory(\App\Training::class)->create();
        $otherUser = factory(\App\User::class)->create(['group' => null]);
        $this->actingAs($otherUser)->assertTrue(Gate::inspect('viewReports', $training)->denied());
    }

    /** @test */
    public function trainee_cant_access_draft_training_report()
    {
        $report = factory(\App\TrainingReport::class)->create(['draft' => true]);
        $this->actingAs($report->training->user)->assertTrue(Gate::inspect('view', $report)->denied());
    }

    /** @test */
    public function mentor_can_access_draft_training_report()
    {
        $report = factory(\App\TrainingReport::class)->create(['draft' => true]);
        $this->actingAs($report->training->mentors()->first())->assertTrue(Gate::inspect('view', $report)->allowed());
    }

    /** @test */
    public function mentor_can_create_training_report()
    {
        $report = factory(\App\TrainingReport::class)->make();

        $this->actingAs($report->training->mentors()->first())
            ->post(route('training.report.store', ['training' => $report->training->id]), $report->getAttributes())
            ->assertStatus(302);

        $this->assertDatabaseHas('training_reports', $report->getAttributes());
    }

    /** @test */
    public function a_regular_user_cant_create_training_report()
    {
        $report = factory(\App\TrainingReport::class)->make();

        $this->actingAs(factory(\App\User::class)->create(['group' => null]))
            ->post(route('training.report.store', ['training' => $report->training->id]), $report->getAttributes())
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_reports', $report->getAttributes());
    }

    /** @test */
    public function mentor_can_update_a_training_report()
    {
        $report = factory(\App\TrainingReport::class)->create();
        $content = $this->faker->paragraph();

        $this->actingAs($report->training->mentors()->first())
            ->patch(route('training.report.update', ['report' => $report->id]), ['content' => $content])
            ->assertRedirect($report->path());

        $this->assertDatabaseHas('training_reports', ['content' => $content]);

    }

    /** @test */
    public function a_regular_user_cant_update_a_training_report()
    {
        $report = factory(\App\TrainingReport::class)->create();
        $content = $this->faker->paragraph();

        $this->actingAs($report->training->user)
            ->patch(route('training.report.update', ['report' => $report->id]), ['content' => $content])
            ->assertStatus(403);

        $this->assertDatabaseMissing('training_reports', ['content' => $content]);
    }

    /** @test */
    public function mentor_can_delete_a_training_report()
    {
        $report = factory(\App\TrainingReport::class)->create();

        $this->actingAs($report->training->mentors()->first())
            ->delete(route('training.report.delete', ['report' => $report->id]))
            ->assertRedirect(route('training.report.index', ['training' => $report->training->id]));

        $this->assertDatabaseMissing('training_reports', $report->getAttributes());
    }

    /** @test */
    public function another_mentor_cant_delete_training_report()
    {
        $report = factory(\App\TrainingReport::class)->create();
        $otherMentor = factory(\App\User::class)->create(['group' => 3]);

        $this->actingAs($otherMentor)
            ->delete(route('training.report.delete', ['report' => $report->id]))
            ->assertStatus(403);

        $this->assertDatabaseHas('training_reports', $report->getAttributes());
    }

    /** @test */
    public function regular_user_cant_delete_training_report()
    {
        $report = factory(\App\TrainingReport::class)->create();
        $regularUser = factory(\App\User::class)->create(['group' => null]);

        $this->actingAs($regularUser)
            ->delete(route('training.report.delete', ['report' => $report->id]))
            ->assertStatus(403);

        $this->assertDatabaseHas('training_reports', $report->getAttributes());
    }

    /** @test */
    public function another_moderator_can_delete_training_report()
    {
        $report = factory(\App\TrainingReport::class)->create();
        $otherModerator = factory(\App\User::class)->create(['group' => 1]);

        $this->actingAs($otherModerator)
            ->delete(route('training.report.delete', ['report' => $report->id]))
            ->assertRedirect(route('training.report.index', ['training' => $report->training->id]));

        $this->assertDatabaseMissing('training_reports', $report->getAttributes());
    }


}
