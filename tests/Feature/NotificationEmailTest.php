<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Group;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\TrainingInterest;
use App\Models\TrainingReport;
use App\Models\User;
use App\Notifications\TrainingCreatedNotification;
use App\Notifications\TrainingExamNotification;
use App\Notifications\TrainingInterestNotification;
use App\Notifications\TrainingReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Slightly ad-hoc feature test specifically for e-mail notifications.
 */
class NotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Group $moderatorGroup;

    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->moderatorGroup = Group::firstOrCreate(['id' => 2], ['name' => 'Moderator']);
        $this->area = Area::factory()->create();
        $this->user = User::factory()->create([
            'email' => 'personal@example.com',
            'setting_workmail_address' => 'work@example.com',
        ]);
    }

    /**
     * Test a few different notifications to ensure they are sent to the personal email.
     *
     * Limited to notifications that follow the same pattern of taking a training and a related model.
     */
    #[Test]
    #[DataProvider('personalEmailNotificationProvider')]
    public function sends_notification_to_personal_email(string $notificationClass, string $relatedModelClass): void
    {
        $training = Training::factory()->for($this->user)->for($this->area)->create();
        $relatedModel = $relatedModelClass::factory()->for($training)->create();

        $this->user->notify(new $notificationClass($training, $relatedModel));

        Notification::assertSentTo(
            $this->user,
            $notificationClass,
            function ($notification, $channels, $notifiable) {
                $mailData = $notification->toMail($notifiable);
                $this->assertEquals('personal@example.com', $mailData->to[0]['address']);
                $this->assertNotEquals('work@example.com', $mailData->to[0]['address']);

                return true;
            }
        );
    }

    public static function personalEmailNotificationProvider(): array
    {
        return [
            'training report' => [TrainingReportNotification::class, TrainingReport::class],
            'training interest' => [TrainingInterestNotification::class, TrainingInterest::class],
            'training examination' => [TrainingExamNotification::class, TrainingExamination::class],
        ];
    }

    #[Test]
    public function sends_training_request_to_personal_email_and_bcc_to_work_email(): void
    {
        $anotherArea = Area::factory()->create();

        $staffReceivesBcc = User::factory()->create([
            'email' => 'staff.personal@example.com',
            'setting_workmail_address' => 'staff.work@example.com',
            'setting_notify_newreq' => true,
        ]);
        $staffReceivesBcc->groups()->attach($this->moderatorGroup, ['area_id' => $this->area->id]);

        // Staff member who should NOT receive BCC (wrong area)
        $staffWrongArea = User::factory()->create(['setting_notify_newreq' => true]);
        $staffWrongArea->groups()->attach($this->moderatorGroup, ['area_id' => $anotherArea->id]);

        // Staff member who should NOT receive BCC (notification setting disabled)
        $staffNoNotify = User::factory()->create(['setting_notify_newreq' => false]);
        $staffNoNotify->groups()->attach($this->moderatorGroup, ['area_id' => $this->area->id]);

        $training = Training::factory()->for($this->user)->for($this->area)->create();

        $this->user->notify(new TrainingCreatedNotification($training));

        Notification::assertSentTo(
            $this->user,
            TrainingCreatedNotification::class,
            function ($notification, $channels, $notifiable) {
                $mailData = $notification->toMail($notifiable);
                $this->assertEquals('personal@example.com', $mailData->to[0]['address']);

                // Assert that the correct staff member is in the BCCs with their work email,
                // yet is not in the BCCs with their personal email.
                $this->assertTrue(collect($mailData->bcc)->contains('address', 'staff.work@example.com'));
                $this->assertTrue(collect($mailData->bcc)->doesntContain('address', 'staff.personal@example.com'));

                return true;
            }
        );

        // Assert notification was NOT sent to other staff members
        Notification::assertNotSentTo($staffWrongArea, TrainingCreatedNotification::class);
        Notification::assertNotSentTo($staffNoNotify, TrainingCreatedNotification::class);
    }
}
