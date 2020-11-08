<?php

namespace Tests\Feature;

use App\File;
use App\Training;
use App\TrainingObjectAttachment;
use App\TrainingReport;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainingObjectAttachmentTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    private $report;
    private $user;

    /**
     * Provide report to use throughout the tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create(['id' => 10000005, 'group' => null]);
        $this->report = factory(TrainingReport::class)->create([
            'training_id' => factory(Training::class)->create([
                'user_id' => $this->user->id,
            ])->id,
            'written_by_id' => factory(User::class)->create([
                'id' => 10000001,
                'group' => 2,
            ])->id,
        ]);

    }

    /**
     * Automatically delete the files that were uploaded during the tests.
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        Storage::deleteDirectory('/public');
        parent::tearDown();
    }

    /** @test */
    public function mentor_can_upload_an_attachment()
    {
        $this->withoutExceptionHandling();
        $mentor = $this->report->author;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $response = $this->actingAs($mentor)->postJson(route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file]);
        $id = $response->decodeResponseJson('id');

        $this->assertDatabaseHas('training_object_attachments', ['id' => $id]);
        $attachments = TrainingObjectAttachment::find($id);
        Storage::disk('test')->assertExists($attachments->first()->file->full_path);
    }

    /** @test */
    public function student_cant_upload_an_attachment()
    {
        $student = $this->user;
        $file = UploadedFile::fake()->image($this->faker->word);

        $response = $this->actingAs($student)->postJson(route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file]);
        $response->assertStatus(403);
        $id = $response->decodeResponseJson('id');

        $this->assertDatabaseMissing('training_object_attachments', ['id' => $id]);
        $this->assertNull(File::find($id));
    }

    /** @test */
    public function mentor_can_see_attachments()
    {
        $mentor = $this->report->author;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $id = $this->actingAs($mentor)
            ->postJson(route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file])
            ->decodeResponseJson('id')[0];

        $this->followingRedirects()->get(route('training.object.attachment.show', ['attachment' => $id]))
            ->assertStatus(200);
    }

    /** @test */
    public function student_can_see_not_hidden_attachment()
    {
        $student = $this->report->training->user;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        // We force-update report to not be a draft
        $this->report->update(['draft' => 0]);

        $id = $this->actingAs($this->report->author)
            ->postJson(route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file])
            ->decodeResponseJson('id')[0];

        $this->actingAs($student)->followingRedirects()
            ->get(route('training.object.attachment.show', ['attachment' => $id]))
            ->assertStatus(200);

    }

// TODO: Re-enable this test once hidden / not hidden has been fully implemented

//    /** @test */
//    public function student_cant_access_hidden_attachment()
//    {
//        $student = $this->report->training->user;
//        $file = UploadedFile::fake()->image($this->faker->word);
//
//        $id = $this->actingAs($this->report->user)
//            ->postJson(route('training.report.attachment.store', ['report' => $this->report, 'hidden' => true]), ['file' => $file])
//            ->decodeResponseJson('id')[0];
//
//        $this->actingAs($student)->followingRedirects()
//            ->get(route('training.report.attachment.show', ['attachment' => $id]))
//            ->assertStatus(403);
//    }

    /** @test */
    public function mentor_can_access_hidden_attachment()
    {
        $mentor = $this->report->author;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $id = $this->actingAs($mentor)
            ->postJson(route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report, 'hidden' => true]), ['file' => $file])
            ->decodeResponseJson('id')[0];

        $this->actingAs($mentor)->followingRedirects()
            ->get(route('training.object.attachment.show', ['attachment' => $id]))
            ->assertStatus(200);
    }


}
