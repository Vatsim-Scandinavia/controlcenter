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
        $mentor = factory(\App\User::class)->create(['group' => 3, 'id' => 10000400]);
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
        $otherUser = factory(\App\User::class)->create(['group' => null, 'id' => 10000134]);
        $this->actingAs($otherUser)->assertTrue(Gate::inspect('viewReports', $training)->denied());
    }

    /** @test */
    public function trainee_cant_access_draft_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000067, 'group' => null])->id,
        ]);

        $mentor = factory(User::class)->create(['id' => 10000159, 'group' => 3]);

        $report = factory(\App\TrainingReport::class)->create([
            'training_id' => $training->id,
            'written_by_id' => $mentor->id,
            'report_date' => now()->addYear(),
            'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum lobortis enim ac commodo lacinia. Nunc scelerisque mauris vitae nisl placerat suscipit. Integer vitae cursus urna, id pulvinar diam. Nunc ullamcorper commodo tellus, nec porta mi hendrerit in. Morbi suscipit id justo eget imperdiet. Cras tempor auctor justo eget aliquet. Cras lectus sapien, maximus nec enim porttitor, pretium mattis tellus. Vivamus dictum turpis eget dolor aliquam euismod. Fusce quis orci nulla. Vivamus congue libero ut ipsum feugiat feugiat. Donec neque erat, egestas eu varius et, volutpat ut augue. Etiam ac rutrum elit, at iaculis ligula. Vestibulum viverra libero ligula, ac euismod tellus bibendum eu.',
            'contentimprove' => null,
            'position' => null,
            'draft' => true,
        ]);
        $this->actingAs($report->training->user)->assertTrue(Gate::inspect('view', $report)->denied());
    }

    /** @test */
    public function mentor_can_access_draft_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000042])->id,
        ]);

        $mentor = factory(User::class)->create(['id' => 10000080, 'group' => 3]);

        $report = factory(\App\TrainingReport::class)->create([
            'training_id' => $training->id,
            'written_by_id' => $mentor->id,
            'report_date' => now()->addYear(),
            'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum lobortis enim ac commodo lacinia. Nunc scelerisque mauris vitae nisl placerat suscipit. Integer vitae cursus urna, id pulvinar diam. Nunc ullamcorper commodo tellus, nec porta mi hendrerit in. Morbi suscipit id justo eget imperdiet. Cras tempor auctor justo eget aliquet. Cras lectus sapien, maximus nec enim porttitor, pretium mattis tellus. Vivamus dictum turpis eget dolor aliquam euismod. Fusce quis orci nulla. Vivamus congue libero ut ipsum feugiat feugiat. Donec neque erat, egestas eu varius et, volutpat ut augue. Etiam ac rutrum elit, at iaculis ligula. Vestibulum viverra libero ligula, ac euismod tellus bibendum eu.',
            'contentimprove' => null,
            'position' => null,
            'draft' => true,
        ]);


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
            'written_by_id' => null,
            'report_date' => now()->addYear(),
            'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum lobortis enim ac commodo lacinia. Nunc scelerisque mauris vitae nisl placerat suscipit. Integer vitae cursus urna, id pulvinar diam. Nunc ullamcorper commodo tellus, nec porta mi hendrerit in. Morbi suscipit id justo eget imperdiet. Cras tempor auctor justo eget aliquet. Cras lectus sapien, maximus nec enim porttitor, pretium mattis tellus. Vivamus dictum turpis eget dolor aliquam euismod. Fusce quis orci nulla. Vivamus congue libero ut ipsum feugiat feugiat. Donec neque erat, egestas eu varius et, volutpat ut augue. Etiam ac rutrum elit, at iaculis ligula. Vestibulum viverra libero ligula, ac euismod tellus bibendum eu.',
            'contentimprove' => null,
            'position' => null,
            'draft' => true,
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

        $mentor = factory(User::class)->create(['id' => 10000500, 'group' => 3]);

        $report = factory(\App\TrainingReport::class)->create([
            'training_id' => $training->id,
            'written_by_id' => $mentor->id,
            'report_date' => now()->addYear(),
            'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum lobortis enim ac commodo lacinia. Nunc scelerisque mauris vitae nisl placerat suscipit. Integer vitae cursus urna, id pulvinar diam. Nunc ullamcorper commodo tellus, nec porta mi hendrerit in. Morbi suscipit id justo eget imperdiet. Cras tempor auctor justo eget aliquet. Cras lectus sapien, maximus nec enim porttitor, pretium mattis tellus. Vivamus dictum turpis eget dolor aliquam euismod. Fusce quis orci nulla. Vivamus congue libero ut ipsum feugiat feugiat. Donec neque erat, egestas eu varius et, volutpat ut augue. Etiam ac rutrum elit, at iaculis ligula. Vestibulum viverra libero ligula, ac euismod tellus bibendum eu.',
            'contentimprove' => null,
            'position' => null,
            'draft' => false,
        ]);

        $report->training->country->mentors()->attach($mentor);

        $training->mentors()->attach($mentor, ['expire_at' => now()->addYear()]);

        $this->actingAs($mentor)
            ->get(route('training.report.delete', ['report' => $report->id]));

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
            ->get(route('training.report.delete', ['report' => $report->id]))
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
            ->get(route('training.report.delete', ['report' => $report->id]))
            ->assertStatus(403);

        $this->assertDatabaseHas('training_reports', $report->getAttributes());
    }

    /** @test */
    public function another_moderator_can_delete_training_report()
    {
        $training = factory(\App\Training::class)->create([
            'user_id' => factory(User::class)->create(['id' => 10000098])->id,
        ]);

        $mentor = factory(User::class)->create([
            'id' => 10000220,
            'group' => 3,
        ]);

        $report = factory(\App\TrainingReport::class)->create([
            'training_id' => $training->id,
            'written_by_id' => $mentor->id,
            'report_date' => now()->addYear(),
            'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum lobortis enim ac commodo lacinia. Nunc scelerisque mauris vitae nisl placerat suscipit. Integer vitae cursus urna, id pulvinar diam. Nunc ullamcorper commodo tellus, nec porta mi hendrerit in. Morbi suscipit id justo eget imperdiet. Cras tempor auctor justo eget aliquet. Cras lectus sapien, maximus nec enim porttitor, pretium mattis tellus. Vivamus dictum turpis eget dolor aliquam euismod. Fusce quis orci nulla. Vivamus congue libero ut ipsum feugiat feugiat. Donec neque erat, egestas eu varius et, volutpat ut augue. Etiam ac rutrum elit, at iaculis ligula. Vestibulum viverra libero ligula, ac euismod tellus bibendum eu.',
            'contentimprove' => null,
            'position' => null,
            'draft' => false,
        ]);
        $otherModerator = factory(\App\User::class)->create(['group' => 1, 'id' => 10000101]);

        $this->actingAs($otherModerator)
            ->get(route('training.report.delete', ['report' => $report->id]));

        $this->assertDatabaseMissing('training_reports', $report->getAttributes());
    }


}
