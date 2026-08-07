<?php

namespace App\Livewire;

use App\Contracts\ComboboxOptionProvider;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Reactive;
use Livewire\Component;

/**
 * A generic, reusable free-text-plus-suggestions combobox.
 *
 * It stays lazy: no options are queried or rendered until the user has typed
 * at least {@see $minChars} characters, and the option set is delegated to a
 * pluggable {@see ComboboxOptionProvider} (which applies its own scope and
 * limit). The typed/selected string is the value, bound to the parent via
 * wire:model.
 */
class Combobox extends Component
{
    #[Modelable]
    public string $value = '';

    /** FQCN of a ComboboxOptionProvider. Locked so a tampered request cannot swap it. */
    #[Locked]
    public string $provider;

    /**
     * Extra, serializable context passed to the provider (e.g. ['area' => 5]).
     * Nullable because it is #[Reactive]: a parent that binds no context pushes
     * null on re-render, which must not crash the component.
     *
     * @var array<string, mixed>|null
     */
    #[Reactive]
    public ?array $context = null;

    public int $minChars = 2;

    public string $placeholder = '';

    public function select(string $value): void
    {
        $this->value = $value;
    }

    public function render(): View
    {
        return view('livewire.combobox', [
            'options' => $this->options(),
        ]);
    }

    /**
     * Matching options, or an empty array while below the character threshold —
     * the provider is not touched until then, so nothing is loaded early.
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function options(): array
    {
        if (mb_strlen(trim($this->value)) < $this->minChars) {
            return [];
        }

        $provider = app($this->provider);

        if (! $provider instanceof ComboboxOptionProvider) {
            return [];
        }

        return $provider->options(trim($this->value), $this->context ?? [])->all();
    }
}
