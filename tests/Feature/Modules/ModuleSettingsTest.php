<?php

namespace Tests\Feature\Modules;

use anlutro\LaravelSettings\Facade as Setting;
use App\Modules\ModuleSettings;
use ControlCenter\ModuleApi\Settings\SettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModuleSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function settings(): ModuleSettings
    {
        return app(ModuleSettings::class);
    }

    #[Test]
    public function it_namespaces_keys_under_the_module_prefix(): void
    {
        $this->assertSame('modules.example.url', $this->settings()->key('example', 'url'));
    }

    #[Test]
    public function secrets_are_encrypted_at_rest_and_decrypted_on_read(): void
    {
        $settings = $this->settings();
        $settings->setSecret('example', 'api_key', 'hunter2');
        $settings->save();

        // Stored value must not be the plaintext.
        $this->assertNotSame('hunter2', Setting::get('modules.example.api_key'));
        // But reading it back decrypts.
        $this->assertSame('hunter2', $settings->getSecret('example', 'api_key'));
        $this->assertTrue($settings->isSet('example', 'api_key'));
    }

    #[Test]
    public function an_unset_secret_reads_as_null(): void
    {
        $this->assertNull($this->settings()->getSecret('example', 'missing'));
        $this->assertFalse($this->settings()->isSet('example', 'missing'));
    }

    #[Test]
    public function persisting_a_schema_stores_values_and_coerces_booleans(): void
    {
        $settings = $this->settings();
        $schema = (new SettingsSchema)
            ->text('greeting', 'Greeting')
            ->boolean('flag', 'Flag')
            ->secret('api_key', 'API key');

        $settings->persistSchema('example', $schema, [
            'greeting' => 'Hello',
            'flag' => '1',
            'api_key' => 'topsecret',
        ]);

        $this->assertSame('Hello', $settings->get('example', 'greeting'));
        $this->assertTrue($settings->get('example', 'flag'));
        $this->assertSame('topsecret', $settings->getSecret('example', 'api_key'));
    }

    #[Test]
    public function a_blank_secret_keeps_the_existing_value(): void
    {
        $settings = $this->settings();
        $settings->setSecret('example', 'api_key', 'original');
        $settings->save();

        $schema = (new SettingsSchema)->secret('api_key', 'API key');
        $settings->persistSchema('example', $schema, ['api_key' => '']);

        $this->assertSame('original', $settings->getSecret('example', 'api_key'));
    }

    #[Test]
    public function an_unchecked_boolean_persists_as_false(): void
    {
        $settings = $this->settings();
        $schema = (new SettingsSchema)->boolean('flag', 'Flag');

        // Unchecked checkboxes submit no key at all.
        $settings->persistSchema('example', $schema, []);

        $this->assertFalse($settings->get('example', 'flag'));
    }
}
