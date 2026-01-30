<?php

namespace Tests\Unit;

use App\Helpers\TrainingStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TrainingStatusTest extends TestCase
{
    #[Test]
    public function label_returns_human_readable_text(): void
    {
        $this->assertSame('In queue', TrainingStatus::IN_QUEUE->label());
        $this->assertSame('Active training', TrainingStatus::ACTIVE_TRAINING->label());
        $this->assertSame('Closed by system', TrainingStatus::CLOSED_BY_SYSTEM->label());
        $this->assertSame('Awaiting exam', TrainingStatus::AWAITING_EXAM->label());
    }

    #[Test]
    public function color_returns_bootstrap_color_class(): void
    {
        $this->assertSame('danger', TrainingStatus::CLOSED_BY_SYSTEM->color());
        $this->assertSame('success', TrainingStatus::COMPLETED->color());
        $this->assertSame('warning', TrainingStatus::IN_QUEUE->color());
        $this->assertSame('info', TrainingStatus::PRE_TRAINING->color());
    }

    #[Test]
    public function icon_returns_fa_class_string(): void
    {
        $this->assertSame('fas fa-graduation-cap', TrainingStatus::AWAITING_EXAM->icon());
        $this->assertSame('fas fa-check', TrainingStatus::COMPLETED->icon());
    }

    #[Test]
    public function is_assignable_by_staff_returns_false_for_system_closed_statuses(): void
    {
        $this->assertFalse(TrainingStatus::CLOSED_BY_SYSTEM->isAssignableByStaff());
        $this->assertFalse(TrainingStatus::CLOSED_BY_STUDENT->isAssignableByStaff());
    }

    #[Test]
    public function is_assignable_by_staff_returns_true_for_staff_assignable_statuses(): void
    {
        $this->assertTrue(TrainingStatus::CLOSED_BY_STAFF->isAssignableByStaff());
        $this->assertTrue(TrainingStatus::IN_QUEUE->isAssignableByStaff());
        $this->assertTrue(TrainingStatus::ACTIVE_TRAINING->isAssignableByStaff());
    }
}
