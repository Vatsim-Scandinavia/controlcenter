<?php

namespace App\Support\Comboboxes;

use App\Contracts\ComboboxOptionProvider;
use App\Models\Feedback;
use App\Models\User;
use App\Services\Sql\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Combobox options for the feedback controller filter: controllers referenced
 * by feedback the current user is allowed to see, matched by name or id.
 */
class FeedbackControllerOptions implements ComboboxOptionProvider
{
    public const LIMIT = 15;

    public function options(string $search, array $context = []): Collection
    {
        $referencedIds = Feedback::visibleTo(auth()->user())
            ->whereNotNull('reference_user_id')
            ->distinct()
            ->pluck('reference_user_id');

        return User::whereIn('id', $referencedIds)
            ->where(fn (Builder $q) => $q
                ->whereRaw(Sql::concat('first_name', "' '", 'last_name') . ' like ?', ['%' . $search . '%'])
                ->orWhere('id', 'like', '%' . $search . '%'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (User $user): array => [
                'value' => $user->name,
                'label' => "{$user->name} ({$user->id})",
            ]);
    }
}
