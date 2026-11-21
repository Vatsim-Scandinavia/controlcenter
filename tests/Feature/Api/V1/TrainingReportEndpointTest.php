<?php

namespace Tests\Feature\Api\V1;

use App\Helpers\TrainingStatus;
use App\Models\ApiKey;
use App\Models\Area;
use App\Models\Position;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\TrainingReport;
use App\Models\User;
use App\Notifications\TrainingExamNotification;
use App\Notifications\TrainingReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainingReportEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function editKey(): string
    {
        ApiKey::firstOrCreate(['id' => 'report-edit-key'], ['name' => 't', 'read_only' => false, 'created_at' => now()]);

        return 'report-edit-key';
    }

    private function readOnlyKey(): string
    {
        ApiKey::firstOrCreate(['id' => 'report-ro-key'], ['name' => 't', 'read_only' => true, 'created_at' => now()]);

        return 'report-ro-key';
    }

    private function makeTraining(TrainingStatus $status = TrainingStatus::ACTIVE_TRAINING): Training
    {
        return Training::factory()->create([
            'user_id' => User::factory()->create(['setting_notify_newreport' => true])->id,
            'area_id' => Area::factory()->create()->id,
            'status' => $status->value,
        ]);
    }

    private function makePosition(): Position
    {
        return Position::factory()->create();
    }

    public function test_report_endpoints_reject_missing_and_read_only_keys_for_writes(): void
    {
        $training = $this->makeTraining();
        $url = '/api/v1/trainings/' . $training->id . '/reports';

        $this->getJson($url)->assertUnauthorized();
        $this->postJson($url, [])->assertUnauthorized();
        $this->withToken($this->readOnlyKey())->postJson($url, [])->assertUnauthorized();
        $this->withToken($this->readOnlyKey())->getJson($url)->assertOk();
    }

    public function test_report_crud_happy_path(): void
    {
        Notification::fake();
        $training = $this->makeTraining();
        $author = User::factory()->create();
        $token = $this->editKey();
        $url = '/api/v1/trainings/' . $training->id . '/reports';

        $created = $this->withToken($token)->postJson($url, [
            'content' => 'Good session',
            'contentimprove' => 'Phraseology',
            'report_date' => '2026-02-15',
            'position' => 'LPPT_TWR',
            'actor_cid' => $author->id,
        ])->assertCreated()
            ->assertJsonPath('data.content', 'Good session')
            ->assertJsonPath('data.written_by_id', $author->id)
            ->assertJsonPath('data.draft', false);

        $this->assertNotNull($created->json('data.published_at'));
        $this->assertSame('2026-02-15', substr($created->json('data.report_date'), 0, 10));
        Notification::assertSentTo($training->user, TrainingReportNotification::class);

        $reportId = $created->json('data.id');

        $this->withToken($token)->getJson($url)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
        $this->withToken($token)->getJson($url . '/' . $reportId)->assertOk()->assertJsonPath('data.id', $reportId);

        $this->withToken($token)->patchJson($url . '/' . $reportId, ['content' => 'Updated content'])
            ->assertOk()
            ->assertJsonPath('data.content', 'Updated content');

        $this->withToken($token)->deleteJson($url . '/' . $reportId)->assertOk()->assertJsonPath('data.id', $reportId);
        $this->assertDatabaseMissing('training_reports', ['id' => $reportId]);
    }

    public function test_draft_reports_notify_only_when_published(): void
    {
        Notification::fake();
        $training = $this->makeTraining();
        $token = $this->editKey();
        $url = '/api/v1/trainings/' . $training->id . '/reports';

        $draft = $this->withToken($token)->postJson($url, ['content' => 'WIP', 'report_date' => '2026-02-15', 'draft' => true])
            ->assertCreated()
            ->assertJsonPath('data.draft', true)
            ->assertJsonPath('data.published_at', null);
        Notification::assertNothingSent();

        $this->withToken($this->readOnlyKey())->getJson($url . '?draft=1')->assertOk()->assertJsonCount(1, 'data');
        $this->withToken($this->readOnlyKey())->getJson($url . '?draft=0')->assertOk()->assertJsonCount(0, 'data');

        $this->withToken($token)->patchJson($url . '/' . $draft->json('data.id'), ['draft' => false])
            ->assertOk()
            ->assertJsonPath('data.draft', false);
        Notification::assertSentTo($training->user, TrainingReportNotification::class);
    }

    public function test_report_requires_a_training_in_progress_and_valid_input(): void
    {
        $queued = $this->makeTraining(TrainingStatus::IN_QUEUE);
        $token = $this->editKey();

        $this->withToken($token)->postJson('/api/v1/trainings/' . $queued->id . '/reports', ['content' => 'x', 'report_date' => '2026-02-15'])
            ->assertStatus(409);

        $active = $this->makeTraining();
        $this->withToken($token)->postJson('/api/v1/trainings/' . $active->id . '/reports', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content', 'report_date']);
    }

    public function test_report_of_another_training_is_not_found(): void
    {
        $training = $this->makeTraining();
        $other = $this->makeTraining();
        $report = TrainingReport::factory()->create(['training_id' => $other->id, 'written_by_id' => User::factory()->create()->id, 'position' => 'LPPT_TWR']);

        $this->withToken($this->editKey())->getJson('/api/v1/trainings/' . $training->id . '/reports/' . $report->id)->assertNotFound();
    }

    public function test_attachments_can_be_uploaded_shown_and_deleted(): void
    {
        Storage::fake();
        $training = $this->makeTraining();
        $uploader = User::factory()->create();
        $report = TrainingReport::factory()->create(['training_id' => $training->id, 'written_by_id' => $uploader->id, 'position' => 'LPPT_TWR']);
        $token = $this->editKey();

        $response = $this->withToken($token)->post('/api/v1/trainings/' . $training->id . '/reports/' . $report->id . '/attachments', [
            'files' => [UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf')],
            'actor_cid' => $uploader->id,
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'notes.pdf')
            ->assertJsonPath('data.0.hidden', false);

        $attachmentId = $response->json('data.0.id');
        $this->assertDatabaseHas('files', ['name' => 'notes.pdf', 'uploaded_by' => $uploader->id]);

        $this->withToken($this->readOnlyKey())->get('/api/v1/attachments/' . $attachmentId)->assertOk();
        $this->withToken($this->readOnlyKey())->getJson('/api/v1/trainings/' . $training->id . '/reports/' . $report->id)
            ->assertJsonPath('data.attachments.0.id', $attachmentId);

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/reports/' . $report->id . '/attachments', [])
            ->assertStatus(422);

        $this->withToken($token)->deleteJson('/api/v1/attachments/' . $attachmentId)->assertOk();
        $this->assertDatabaseMissing('training_object_attachments', ['id' => $attachmentId]);
    }

    public function test_examination_crud_happy_path(): void
    {
        Notification::fake();
        $training = $this->makeTraining(TrainingStatus::AWAITING_EXAM);
        $position = $this->makePosition();
        $examiner = User::factory()->create();
        $token = $this->editKey();
        $url = '/api/v1/trainings/' . $training->id . '/examinations';

        $created = $this->withToken($token)->postJson($url, [
            'position' => $position->callsign,
            'result' => 'PASSED',
            'examination_date' => '2026-03-01',
            'actor_cid' => $examiner->id,
        ])->assertCreated()
            ->assertJsonPath('data.result', 'PASSED')
            ->assertJsonPath('data.position', $position->callsign)
            ->assertJsonPath('data.examiner_id', $examiner->id);

        Notification::assertSentTo($training->user, TrainingExamNotification::class);
        $examinationId = $created->json('data.id');

        $this->withToken($token)->getJson($url)->assertOk()->assertJsonCount(1, 'data');
        $this->withToken($token)->getJson($url . '?result=FAILED')->assertOk()->assertJsonCount(0, 'data');

        $this->withToken($token)->patchJson($url . '/' . $examinationId, ['result' => 'FAILED'])
            ->assertOk()
            ->assertJsonPath('data.result', 'FAILED');

        $this->withToken($token)->deleteJson($url . '/' . $examinationId)->assertOk();
        $this->assertNull(TrainingExamination::find($examinationId));
    }

    public function test_examination_requires_a_training_awaiting_exam(): void
    {
        $training = $this->makeTraining(TrainingStatus::ACTIVE_TRAINING);
        $position = $this->makePosition();

        $this->withToken($this->editKey())->postJson('/api/v1/trainings/' . $training->id . '/examinations', [
            'position' => $position->callsign,
            'result' => 'PASSED',
        ])->assertStatus(409);
    }
}
