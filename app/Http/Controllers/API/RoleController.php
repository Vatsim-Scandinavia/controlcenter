<?php

namespace App\Http\Controllers\API;

use App\Actions\GrantRole;
use App\Actions\RevokeRole;
use App\Http\Controllers\API\Concerns\ResolvesApiActor;
use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\User;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    use ResolvesApiActor;

    /**
     * Grant a role to a user.
     *
     * The `admin` role cannot be granted through the API.
     */
    #[Response(status: 201, description: 'The role was granted.')]
    #[Response(status: 422, description: 'Validation failed, admin was requested, or the Division API rejected the write.')]
    public function store(Request $request, User $user, GrantRole $grantRole): JsonResponse
    {
        $roles = array_keys(config('roles.roles', []));
        $roles = array_values(array_filter($roles, fn (string $role) => $role !== 'admin'));

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in($roles)],
            'area_id' => 'nullable|integer|exists:areas,id',
            'actor_cid' => 'required|integer|exists:users,id',
        ]);

        $actor = $this->requireActor($request);
        $area = isset($data['area_id']) ? Area::findOrFail($data['area_id']) : null;

        $scope = config("roles.roles.{$data['role']}.scope");
        if ($scope === 'global' && $area !== null) {
            return response()->json(['message' => 'This role cannot be scoped to an area.'], 422);
        }
        if ($scope === 'area' && $area === null) {
            return response()->json(['message' => 'This role requires an area.'], 422);
        }

        $error = $grantRole($actor, $user, $data['role'], $area, authorize: false);
        if ($error !== null) {
            return response()->json(['message' => $error], 422);
        }

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'role' => $data['role'],
                'area_id' => $area?->id,
            ],
        ], 201);
    }

    /**
     * Revoke a role from a user.
     */
    #[Response(status: 200, description: 'The role was revoked.')]
    #[Response(status: 422, description: 'Validation failed, admin was requested, or the Division API rejected the write.')]
    public function destroy(Request $request, User $user, RevokeRole $revokeRole): JsonResponse
    {
        $roles = array_keys(config('roles.roles', []));
        $roles = array_values(array_filter($roles, fn (string $role) => $role !== 'admin'));

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in($roles)],
            'area_id' => 'nullable|integer|exists:areas,id',
            'actor_cid' => 'required|integer|exists:users,id',
        ]);

        $actor = $this->requireActor($request);
        $area = isset($data['area_id']) ? Area::findOrFail($data['area_id']) : null;

        $error = $revokeRole($actor, $user, $data['role'], $area, authorize: false);
        if ($error !== null) {
            return response()->json(['message' => $error], 422);
        }

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'role' => $data['role'],
                'area_id' => $area?->id,
            ],
        ], 200);
    }
}
