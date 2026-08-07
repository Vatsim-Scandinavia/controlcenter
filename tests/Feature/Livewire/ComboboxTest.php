<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Combobox;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\SpyComboboxProvider;
use Tests\TestCase;

class ComboboxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SpyComboboxProvider::$calls = 0;
    }

    private function combobox(int $minChars = 2): Testable
    {
        return Livewire::test(Combobox::class, [
            'provider' => SpyComboboxProvider::class,
            'minChars' => $minChars,
        ]);
    }

    #[Test]
    public function it_does_not_query_options_below_the_minimum_characters(): void
    {
        $this->combobox(minChars: 2)
            ->set('value', 'a') // one char, below threshold
            ->assertViewHas('options', fn ($options) => $options === []);

        $this->assertSame(0, SpyComboboxProvider::$calls, 'Provider must not be queried below minChars.');
    }

    #[Test]
    public function it_queries_options_once_the_threshold_is_reached(): void
    {
        $this->combobox(minChars: 2)
            ->set('value', 'al') // matches "Alpha"
            ->assertViewHas('options', fn ($options) => count($options) === 1 && $options[0]['value'] === 'Alpha');

        $this->assertSame(1, SpyComboboxProvider::$calls);
    }

    #[Test]
    public function it_tolerates_a_null_context(): void
    {
        // Regression: context is #[Reactive], so a parent that binds no context
        // (e.g. the controller combobox) can push null on re-render. The generic
        // component must treat that as "no context", not crash.
        Livewire::test(Combobox::class, [
            'provider' => SpyComboboxProvider::class,
            'minChars' => 2,
            'context' => null,
        ])
            ->set('value', 'al')
            ->assertViewHas('options', fn ($options) => count($options) === 1 && $options[0]['value'] === 'Alpha');
    }

    #[Test]
    public function selecting_an_option_sets_the_value(): void
    {
        $this->combobox()
            ->call('select', 'Beta')
            ->assertSet('value', 'Beta');
    }

    #[Test]
    public function it_keeps_arbitrary_free_text_as_the_value(): void
    {
        $this->combobox()
            ->set('value', 'something not in the list')
            ->assertSet('value', 'something not in the list');
    }

    #[Test]
    public function the_provider_class_is_locked_against_client_tampering(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->combobox()->set('provider', 'App\\Support\\Comboboxes\\FeedbackPositionOptions');
    }
}
