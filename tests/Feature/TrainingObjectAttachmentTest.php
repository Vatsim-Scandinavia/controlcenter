<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Training;
use App\Models\TrainingObjectAttachment;
use App\Models\TrainingReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrainingObjectAttachmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $report;

    private $user;

    /**
     * Provide report to use throughout the tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['id' => 10000005]);
        $this->report = TrainingReport::factory()->create([
            'training_id' => Training::factory()->create([
                'user_id' => $this->user->id,
            ])->id,
            'written_by_id' => User::factory()->create([
                'id' => 10000001,
            ])->id,
        ]);

        $this->report->author->groups()->attach(2, ['area_id' => $this->report->training->area->id]);
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

    #[Test]
    public function mentor_can_upload_an_attachment()
    {
        $this->withoutExceptionHandling();
        $mentor = $this->report->author;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $response = $this->actingAs($mentor)->postJson(route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file]);
        $id = $response->json('id');

        $this->assertDatabaseHas('training_object_attachments', ['id' => $id]);
        $attachments = TrainingObjectAttachment::find($id);
        Storage::disk('test')->assertExists($attachments->first()->file->full_path);
    }

    #[Test]
    public function student_cant_upload_an_attachment()
    {
        $student = $this->user;
        $file = UploadedFile::fake()->image($this->faker->word);

        $response = $this->actingAs($student)->postJson(route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file]);
        $response->assertStatus(403);
        $id = $response->json('id');

        $this->assertDatabaseMissing('training_object_attachments', ['id' => $id]);
        $this->assertNull(File::find($id));
    }

    #[Test]
    public function mentor_can_see_attachments()
    {
        $mentor = $this->report->author;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $id = $this->actingAs($mentor)
            ->postJson(route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file])
            ->json('id')[0];

        $this->followingRedirects()->get(route('training.object.attachment.show', ['attachment' => $id]))
            ->assertStatus(200);
    }

    #[Test]
    public function student_can_see_not_hidden_attachment()
    {
        $student = $this->report->training->user;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        // We force-update report to not be a draft
        $this->report->update(['draft' => 0]);

        $id = $this->actingAs($this->report->author)
            ->postJson(route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report]), ['file' => $file])
            ->json('id')[0];

        $this->actingAs($student)->followingRedirects()
            ->get(route('training.object.attachment.show', ['attachment' => $id]))
            ->assertStatus(200);
    }

    #[Test]
    public function mentor_can_access_hidden_attachment()
    {
        $mentor = $this->report->author;
        $file = UploadedFile::fake()->image($this->faker->word . '.jpg');

        $id = $this->actingAs($mentor)
            ->postJson(route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $this->report, 'hidden' => true]), ['file' => $file])
            ->json('id')[0];

        $this->actingAs($mentor)->followingRedirects()
            ->get(route('training.object.attachment.show', ['attachment' => $id]))
            ->assertStatus(200);
    }
}
