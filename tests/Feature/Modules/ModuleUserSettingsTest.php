<?php

namespace Tests\Feature\Modules;

use App\Models\ModuleUserSetting;
use App\Models\User;
use App\Modules\ModuleUserSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

/**
 * Finding 10: per-user settings declared via Registrar::userSettings() must be
 * rendered, validated and persisted by core, with secret fields encrypted and
 * never echoed — the same treatment as admin module settings.
 */
class ModuleUserSettingsTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    #[Test]
    public function the_user_settings_page_renders_the_module_schema(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $this->actingAs($this->user())
            ->get(route('user.settings'))
            ->assertOk()
            ->assertSee('Nickname')
            ->assertSee('Personal token');
    }

    #[Test]
    public function it_persists_per_user_values_and_encrypts_secrets(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('user.settings.modules.store', 'example'), [
                'nickname' => 'Maverick',
                'personal_token' => 'tok-12345',
            ])
            ->assertRedirect(route('user.settings'));

        $settings = app(ModuleUserSettings::class);
        $this->assertSame('Maverick', $settings->get($user->id, 'example', 'nickname'));
        $this->assertSame('tok-12345', $settings->getSecret($user->id, 'example', 'personal_token'));

        // Stored at rest encrypted, not in cleartext.
        $raw = ModuleUserSetting::query()->where('user_id', $user->id)->where('key', 'modules.example.personal_token')->value('value');
        $this->assertNotSame('tok-12345', $raw);
        $this->assertNotEmpty($raw);

        // Values are scoped per user.
        $this->assertNull($settings->get($this->user()->id, 'example', 'nickname'));
    }

    #[Test]
    public function the_secret_is_never_rendered_back_in_cleartext(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);
        $user = $this->user();

        app(ModuleUserSettings::class)->setSecret($user->id, 'example', 'personal_token', 'tok-secret');

        $this->actingAs($user)
            ->get(route('user.settings'))
            ->assertOk()
            ->assertDontSee('tok-secret');
    }

    #[Test]
    public function validation_failure_keeps_the_secret_out_of_old_input(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('user.settings.modules.store', 'example'), [
                'nickname' => str_repeat('x', 100), // exceeds max:50
                'personal_token' => 'leak-me',
            ])
            ->assertRedirect(route('user.settings'));

        $this->assertNull(session()->getOldInput('personal_token'), 'The per-user secret was flashed to old input.');
    }

    #[Test]
    public function a_disabled_module_has_no_user_settings_endpoint(): void
    {
        $this->bootModules([ExampleModule::class]); // not enabled

        $this->actingAs($this->user())
            ->post(route('user.settings.modules.store', 'example'), ['nickname' => 'x'])
            ->assertNotFound();
    }
}
