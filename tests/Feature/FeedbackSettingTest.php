<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeedbackSettingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function feedback_enabled_setting_is_seeded_with_the_correct_key(): void
    {
        $table = config('settings.table');

        $this->assertTrue(DB::table($table)->where('key', 'feedbackEnabled')->exists());
        $this->assertFalse(DB::table($table)->where('key', 'feedbackEnable')->exists());
    }

    #[Test]
    public function legacy_key_is_renamed_when_the_correct_key_is_absent(): void
    {
        $table = config('settings.table');

        DB::table($table)->where('key', 'feedbackEnabled')->delete();
        DB::table($table)->insert(['key' => 'feedbackEnable', 'value' => true]);

        (require database_path('migrations/2026_08_07_120000_fix_feedback_enabled_setting_key.php'))->up();

        $this->assertTrue(DB::table($table)->where('key', 'feedbackEnabled')->exists());
        $this->assertFalse(DB::table($table)->where('key', 'feedbackEnable')->exists());
    }
}
