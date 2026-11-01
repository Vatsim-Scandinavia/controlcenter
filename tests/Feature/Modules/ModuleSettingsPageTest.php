<?php

namespace Tests\Feature\Modules;

use App\Models\Area;
use App\Models\User;
use App\Modules\ModuleSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

class ModuleSettingsPageTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }

    #[Test]
    public function an_admin_sees_the_core_rendered_schema(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $this->actingAs($this->admin())
            ->get(route('admin.modules.settings', 'example'))
            ->assertOk()
            ->assertSee('Greeting')
            ->assertSee('API key')
            ->assertSee('An example toggle');
    }

    #[Test]
    public function a_moderator_cannot_view_module_settings(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $this->actingAs($moderator)->get(route('admin.modules.settings', 'example'))->assertForbidden();
    }

    #[Test]
    public function a_secret_value_is_never_echoed_back_to_the_form(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);
        app(ModuleSettings::class)->setSecret('example', 'api_key', 'supersecret');
        app(ModuleSettings::class)->save();

        $this->actingAs($this->admin())
            ->get(route('admin.modules.settings', 'example'))
            ->assertOk()
            ->assertDontSee('supersecret')
            ->assertSee('Enter a new value to replace it.');
    }

    #[Test]
    public function submitting_the_form_validates_and_persists(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);
        $settings = app(ModuleSettings::class);

        $this->actingAs($this->admin())
            ->post(route('admin.modules.settings.store', 'example'), [
                'greeting' => 'Welcome',
                'api_key' => 'newsecret',
                'enabled_flag' => '1',
            ])
            ->assertRedirect(route('admin.modules.settings', 'example'));

        $this->assertSame('Welcome', $settings->get('example', 'greeting'));
        $this->assertTrue($settings->get('example', 'enabled_flag'));
        $this->assertSame('newsecret', $settings->getSecret('example', 'api_key'));
    }

    #[Test]
    public function submitting_a_blank_secret_keeps_the_stored_value(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);
        $settings = app(ModuleSettings::class);
        $settings->setSecret('example', 'api_key', 'keepme');
        $settings->save();

        $this->actingAs($this->admin())
            ->post(route('admin.modules.settings.store', 'example'), [
                'greeting' => 'Hi',
                'api_key' => '',
            ])
            ->assertRedirect(route('admin.modules.settings', 'example'));

        $this->assertSame('keepme', $settings->getSecret('example', 'api_key'));
    }

    #[Test]
    public function validation_rules_from_the_schema_are_enforced(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $this->actingAs($this->admin())
            ->post(route('admin.modules.settings.store', 'example'), [
                'greeting' => str_repeat('a', 300), // schema rule: max:255
            ])
            ->assertSessionHasErrors('greeting');
    }

    #[Test]
    public function a_secret_is_not_flashed_to_old_input_on_validation_failure(): void
    {
        // Finding 4: a validation failure must not flash secret fields to the
        // session in cleartext.
        $this->bootModules([ExampleModule::class], ['example']);

        $this->actingAs($this->admin())
            ->post(route('admin.modules.settings.store', 'example'), [
                'greeting' => str_repeat('a', 300), // fails max:255
                'api_key' => 'super-secret-token',
                'enabled_flag' => '1',
            ])
            ->assertSessionHasErrors('greeting');

        $this->assertNull(session()->getOldInput('api_key'), 'The secret was flashed to old input.');
        $this->assertSame('1', session()->getOldInput('enabled_flag'));
    }
}
