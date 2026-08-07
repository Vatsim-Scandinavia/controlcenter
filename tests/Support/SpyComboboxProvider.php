<?php

namespace Tests\Support;

use App\Contracts\ComboboxOptionProvider;
use Illuminate\Support\Collection;

/**
 * A real (non-mock) provider for exercising the generic Combobox component:
 * it filters a fixed in-memory list by the search term and counts how many
 * times it was called, so tests can prove the component only queries when it
 * should.
 */
class SpyComboboxProvider implements ComboboxOptionProvider
{
    public static int $calls = 0;

    public function options(string $search, array $context = []): Collection
    {
        self::$calls++;

        return collect([
            ['value' => 'Alpha', 'label' => 'Alpha'],
            ['value' => 'Beta', 'label' => 'Beta'],
        ])->filter(fn (array $o): bool => str_contains(strtolower($o['value']), strtolower($search)))
            ->values();
    }
}
