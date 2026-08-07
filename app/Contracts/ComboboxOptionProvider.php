<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface ComboboxOptionProvider
{
    /**
     * Return matching options for the given search term.
     *
     * Implementations MUST apply their own authorization scope and cap the
     * number of results, so a crafted search or context can never surface data
     * the current user may not see, nor load the full option set.
     *
     * @param  array<string, mixed>  $context
     * @return Collection<int, array{value: string, label: string}>
     */
    public function options(string $search, array $context = []): Collection;
}
