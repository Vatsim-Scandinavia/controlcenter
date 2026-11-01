<?php

namespace Tests\Feature\Modules;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Covers the stable module-facing x-cc.* UI kit. Form controls read $errors, so
 * an empty error bag is shared before rendering.
 */
class CcComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
    }

    #[Test]
    public function card_renders_the_bootstrap_card_markup(): void
    {
        $html = Blade::render('<x-cc.card title="My Title">Body text</x-cc.card>');

        $this->assertStringContainsString('card shadow', $html);
        $this->assertStringContainsString('card-header', $html);
        $this->assertStringContainsString('My Title', $html);
        $this->assertStringContainsString('Body text', $html);
    }

    #[Test]
    public function alert_renders_the_typed_bootstrap_alert(): void
    {
        $html = Blade::render('<x-cc.alert type="success">Saved</x-cc.alert>');

        $this->assertStringContainsString('alert alert-success', $html);
        $this->assertStringContainsString('Saved', $html);
    }

    #[Test]
    public function table_renders_headers_and_body(): void
    {
        $html = Blade::render('<x-cc.table :headers="[\'Name\', \'Role\']"><tr><td>Jo</td></tr></x-cc.table>');

        $this->assertStringContainsString('table-responsive', $html);
        $this->assertStringContainsString('<th>Name</th>', $html);
        $this->assertStringContainsString('<th>Role</th>', $html);
        $this->assertStringContainsString('<td>Jo</td>', $html);
    }

    #[Test]
    public function form_emits_csrf_and_method_spoofing(): void
    {
        $html = Blade::render('<x-cc.form action="/x" method="PUT">body</x-cc.form>');

        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="_method"', $html);
        $this->assertStringContainsString('value="PUT"', $html);
    }

    #[Test]
    public function input_renders_a_labelled_control(): void
    {
        $html = Blade::render('<x-cc.input name="url" label="URL" value="https://x" help="help text" />');

        $this->assertStringContainsString('for="url"', $html);
        $this->assertStringContainsString('URL', $html);
        $this->assertStringContainsString('form-control', $html);
        $this->assertStringContainsString('https://x', $html);
        $this->assertStringContainsString('help text', $html);
    }

    #[Test]
    public function select_renders_options_and_marks_the_current_value(): void
    {
        $html = Blade::render('<x-cc.select name="k" label="Pick" :options="[\'a\' => \'Alpha\', \'b\' => \'Beta\']" value="b" />');

        $this->assertStringContainsString('form-select', $html);
        $this->assertStringContainsString('<option value="a"', $html);
        $this->assertStringContainsString('Alpha', $html);
        $this->assertStringContainsString('selected', $html);
    }

    #[Test]
    public function textarea_renders_its_value(): void
    {
        $html = Blade::render('<x-cc.textarea name="notes" label="Notes" value="hello" />');

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('hello', $html);
    }

    #[Test]
    public function checkbox_reflects_the_checked_state(): void
    {
        $html = Blade::render('<x-cc.checkbox name="flag" label="Enable" :checked="true" />');

        $this->assertStringContainsString('form-check-input', $html);
        $this->assertStringContainsString('checked', $html);
        $this->assertStringContainsString('Enable', $html);
    }

    #[Test]
    public function secret_shows_set_state_without_any_value(): void
    {
        $setHtml = Blade::render('<x-cc.secret name="api" label="API key" :isSet="true" />');
        $this->assertStringContainsString('Set', $setHtml);
        $this->assertStringContainsString('type="password"', $setHtml);

        $unsetHtml = Blade::render('<x-cc.secret name="api" label="API key" :isSet="false" />');
        $this->assertStringContainsString('Not set', $unsetHtml);
    }
}
