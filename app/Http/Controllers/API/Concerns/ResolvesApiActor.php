<?php

namespace App\Http\Controllers\API\Concerns;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Support\CauserResolver;

trait ResolvesApiActor
{
    /**
     * Resolve the optional VATSIM CID used as the Division API requester and activity-log causer.
     *
     * The edit key is the authorization. This CID is never checked against that user's roles.
     *
     * @throws ValidationException
     */
    protected function resolveActor(Request $request, bool $required = false): ?User
    {
        $rules = [
            'actor_cid' => [$required ? 'required' : 'sometimes', 'integer', 'exists:users,id'],
        ];

        $validated = $request->validate($rules);

        if (! isset($validated['actor_cid'])) {
            return null;
        }

        $actor = User::findOrFail($validated['actor_cid']);
        app(CauserResolver::class)->setCauser($actor);

        return $actor;
    }

    /**
     * Require actor_cid and return the matching user.
     *
     * @throws ValidationException
     */
    protected function requireActor(Request $request): User
    {
        return $this->resolveActor($request, required: true);
    }
}
