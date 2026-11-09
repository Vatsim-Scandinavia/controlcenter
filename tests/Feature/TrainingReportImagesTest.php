<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Training;
use App\Models\TrainingObjectAttachment;
use App\Models\TrainingReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class TrainingReportImagesTest extends TestCase
{
    use RefreshDatabase;

    private Training $training;

    private User $mentor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        Notification::fake();
        $this->training = Training::factory()->create(['user_id' => User::factory()->create()->id]);
        $this->mentor = User::factory()->create();
        $this->mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $this->training->area->id]);
        $this->training->mentors()->attach($this->mentor, ['expire_at' => now()->addYear()]);
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('radar.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9mQAAAAASUVORK5CYII='
        ));
    }

    #[Test]
    public function images_in_both_fields_are_saved_as_attachments_without_duplicates(): void
    {
        $this->actingAs($this->mentor)->post(route('training.report.store', $this->training), [
            'report_date' => today()->format('d/m/Y'),
            'content' => 'Before ![Radar](inline-image:radar) after.',
            'contentimprove' => 'Review ![Radar](inline-image:radar) again.',
            'inline_images' => ['radar' => $this->image()],
            'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'Session notes')],
        ])->assertRedirect();

        $report = $this->training->reports()->firstOrFail();
        $this->assertCount(2, $report->attachments);
        $image = $report->attachments->first(fn ($attachment) => $attachment->file->name === 'radar.png')->file;
        $url = route('file.get', $image);
        $this->assertSame('Before ![Radar](' . $url . ') after.', $report->content);
        $this->assertSame('Review ![Radar](' . $url . ') again.', $report->contentimprove);
        Storage::assertExists($image->full_path);
        $this->actingAs($this->training->user)->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    #[Test]
    public function images_can_be_inserted_when_editing_and_remain_on_later_edits(): void
    {
        $report = TrainingReport::factory()->create([
            'training_id' => $this->training->id,
            'written_by_id' => $this->mentor->id,
            'draft' => true,
        ]);

        $this->actingAs($this->mentor)->patch(route('training.report.update', $report), [
            'report_date' => today()->format('d/m/Y'),
            'content' => 'Report.',
            'contentimprove' => '![Radar](inline-image:radar)',
            'inline_images' => ['radar' => $this->image()],
            'draft' => 1,
        ])->assertRedirect();

        $report->refresh();
        $this->assertCount(1, $report->attachments);
        $url = route('file.get', $report->attachments->first()->file);
        $this->assertSame('![Radar](' . $url . ')', $report->contentimprove);
        $this->actingAs($this->training->user)->get($url)->assertForbidden();

        $this->actingAs($this->mentor)->patch(route('training.report.update', $report), [
            'report_date' => today()->format('d/m/Y'),
            'content' => 'Updated report.',
            'contentimprove' => $report->contentimprove,
        ])->assertRedirect();

        $this->assertCount(1, $report->fresh()->attachments);
        $this->actingAs($this->training->user)->get($url)->assertOk();
    }

    #[Test]
    public function images_removed_from_the_report_are_not_stored(): void
    {
        $this->actingAs($this->mentor)->post(route('training.report.store', $this->training), [
            'report_date' => today()->format('d/m/Y'),
            'content' => 'No image references remain.',
            'inline_images' => ['unused' => $this->image()],
        ])->assertRedirect();

        $this->assertDatabaseCount('files', 0);
        $this->assertDatabaseCount('training_object_attachments', 0);
        $this->assertSame([], Storage::allFiles());
    }

    #[Test]
    public function image_sizes_are_preserved_when_saving_and_resizing_reports(): void
    {
        $this->actingAs($this->mentor)->post(route('training.report.store', $this->training), [
            'report_date' => today()->format('d/m/Y'),
            'content' => '![Radar](inline-image:radar#report-image-width=400)',
            'inline_images' => ['radar' => $this->image()],
        ])->assertRedirect();

        $report = $this->training->reports()->firstOrFail();
        $url = route('file.get', $report->attachments->first()->file);
        $this->assertSame('![Radar](' . $url . '#report-image-width=400)', $report->content);
        $this->actingAs($this->training->user);
        $this->blade('<x-training.training-report :report="$report" />', ['report' => $report->fresh()])->assertSee('width="400"', false);

        $this->actingAs($this->mentor)->patch(route('training.report.update', $report), [
            'report_date' => today()->format('d/m/Y'),
            'content' => '![Radar](' . $url . '#report-image-width=200)',
        ])->assertRedirect();

        $this->assertCount(1, $report->fresh()->attachments);
        $this->actingAs($this->training->user);
        $this->blade('<x-training.training-report :report="$report" />', ['report' => $report->fresh()])->assertSee('width="200"', false);
    }

    #[Test]
    public function missing_images_are_rejected_before_storing_any_files(): void
    {
        $this->actingAs($this->mentor)->postJson(route('training.report.store', $this->training), [
            'report_date' => today()->format('d/m/Y'),
            'content' => '![Present](inline-image:present) ![Missing](inline-image:missing)',
            'inline_images' => ['present' => $this->image()],
        ])->assertUnprocessable()->assertJsonValidationErrors('inline_images');

        $this->assertDatabaseCount('training_reports', 0);
        $this->assertDatabaseCount('files', 0);
        $this->assertSame([], Storage::allFiles());
    }

    #[Test]
    public function files_disguised_as_images_are_rejected(): void
    {
        $this->actingAs($this->mentor)->postJson(route('training.report.store', $this->training), [
            'report_date' => today()->format('d/m/Y'),
            'content' => '![Fake](inline-image:fake)',
            'inline_images' => ['fake' => UploadedFile::fake()->createWithContent('fake.png', '<script>alert(1)</script>')],
        ])->assertUnprocessable()->assertJsonValidationErrors('inline_images.fake');

        $this->assertDatabaseCount('files', 0);
    }

    #[Test]
    public function unauthorized_users_cannot_store_inline_images(): void
    {
        $data = [
            'report_date' => today()->format('d/m/Y'),
            'content' => '![Radar](inline-image:radar)',
            'inline_images' => ['radar' => $this->image()],
        ];
        $report = TrainingReport::factory()->create(['training_id' => $this->training->id]);

        $this->actingAs($this->training->user)->post(route('training.report.store', $this->training), $data)->assertForbidden();
        $this->patch(route('training.report.update', $report), $data)->assertForbidden();
        $this->assertDatabaseCount('files', 0);
        $this->assertSame([], Storage::allFiles());
    }

    #[Test]
    public function unvalidated_upload_fields_are_not_saved_with_reports(): void
    {
        $this->actingAs($this->mentor)->post(route('training.report.store', $this->training), [
            'report_date' => today()->format('d/m/Y'),
            'content' => 'A report with notes.',
            'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'Notes')],
            'unexpected' => UploadedFile::fake()->createWithContent('unexpected.php', '<?php echo "unexpected";'),
        ])->assertRedirect();

        $this->assertDatabaseCount('files', 1);
        $this->assertDatabaseMissing('files', ['name' => 'unexpected.php']);
        $this->assertDatabaseCount('training_object_attachments', 1);
    }

    #[Test]
    public function attachment_endpoint_only_saves_its_validated_file_field(): void
    {
        $report = TrainingReport::factory()->create(['training_id' => $this->training->id]);
        $this->actingAs($this->mentor)->postJson(route('training.object.attachment.store', [
            'trainingObjectType' => 'report', 'trainingObject' => $report,
        ]), [
            'file' => $this->image(),
            'files' => [UploadedFile::fake()->createWithContent('unexpected.php', '<?php echo "unexpected";')],
        ])->assertOk();

        $this->assertDatabaseCount('files', 1);
        $this->assertDatabaseMissing('files', ['name' => 'unexpected.php']);
    }

    #[Test]
    public function images_are_stored_with_a_server_detected_extension(): void
    {
        $image = $this->image();
        $upload = new UploadedFile($image->getPathname(), 'radar.jpg', 'image/png', UPLOAD_ERR_OK, true);
        $this->actingAs($this->mentor)->post(route('training.report.store', $this->training), [
            'report_date' => today()->format('d/m/Y'),
            'content' => '![Radar](inline-image:radar)',
            'inline_images' => ['radar' => $upload],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $file = File::firstOrFail();
        $this->assertStringEndsWith('.png', $file->path);
        $this->get(route('file.get', $file))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    #[Test]
    public function failed_file_writes_do_not_create_report_or_file_records(): void
    {
        Storage::shouldReceive('putFileAs')->once()->andReturn(false);
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->mentor)->post(route('training.report.store', $this->training), [
                'report_date' => today()->format('d/m/Y'),
                'content' => '![Radar](inline-image:radar)',
                'inline_images' => ['radar' => $this->image()],
            ]);
            $this->fail('Expected the upload failure to be reported.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Failed to store the uploaded file.', $exception->getMessage());
        }

        $this->assertDatabaseCount('training_reports', 0);
        $this->assertDatabaseCount('files', 0);
        $this->assertDatabaseCount('training_object_attachments', 0);
    }

    #[Test]
    public function failed_report_creation_rolls_back_image_records_and_stored_files(): void
    {
        TrainingReport::creating(function (): void {
            throw new RuntimeException('Report save failed.');
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->mentor)->post(route('training.report.store', $this->training), [
                'report_date' => today()->format('d/m/Y'),
                'content' => '![Radar](inline-image:radar)',
                'inline_images' => ['radar' => $this->image()],
            ]);
            $this->fail('Expected report creation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Report save failed.', $exception->getMessage());
        }

        $this->assertDatabaseCount('training_reports', 0);
        $this->assertDatabaseCount('files', 0);
        $this->assertSame([], Storage::allFiles());
    }

    #[Test]
    public function failed_attachment_creation_rolls_back_the_report_edit_and_upload(): void
    {
        $report = TrainingReport::factory()->create(['training_id' => $this->training->id, 'content' => 'Original content.']);
        TrainingObjectAttachment::creating(function (): void {
            throw new RuntimeException('Attachment save failed.');
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->mentor)->patch(route('training.report.update', $report), [
                'report_date' => today()->format('d/m/Y'),
                'content' => '![Radar](inline-image:radar)',
                'inline_images' => ['radar' => $this->image()],
            ]);
            $this->fail('Expected attachment creation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Attachment save failed.', $exception->getMessage());
        }

        $this->assertSame('Original content.', $report->fresh()->content);
        $this->assertDatabaseCount('files', 0);
        $this->assertDatabaseCount('training_object_attachments', 0);
        $this->assertSame([], Storage::allFiles());
    }
}
