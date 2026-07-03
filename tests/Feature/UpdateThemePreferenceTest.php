<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_persist_theme_preference_from_toggle(): void
    {
        $user = User::factory()->create(['setting_theme' => 'system']);

        $response = $this->actingAs($user)->post(route('user.settings.theme'), [
            'setting_theme' => 'dark',
        ]);

        $response->assertOk();
        $response->assertJson(['setting_theme' => 'dark']);
        $this->assertEquals('dark', $user->fresh()->setting_theme);
    }

    public function test_theme_preference_rejects_invalid_value(): void
    {
        $user = User::factory()->create(['setting_theme' => 'light']);

        $response = $this->actingAs($user)->postJson(route('user.settings.theme'), [
            'setting_theme' => 'sepia',
        ]);

        $response->assertStatus(422);
        $this->assertEquals('light', $user->fresh()->setting_theme);
    }

    public function test_guest_cannot_persist_theme_preference(): void
    {
        $response = $this->post(route('user.settings.theme'), [
            'setting_theme' => 'dark',
        ]);

        $response->assertRedirect();
    }
}
