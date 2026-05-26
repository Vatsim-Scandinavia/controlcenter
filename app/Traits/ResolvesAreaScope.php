<?php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Handles area-scoping for report and similar views where the user may have access
 * to one area, many areas, or all areas.
 *
 * Call resolveAreaScope() at the top of any controller action that accepts an optional
 * area filter. It returns non-null when the caller should return early (redirect to a
 * specific area, or show the area picker); null means the user is a global admin and
 * should proceed to load data unfiltered.
 */
trait ResolvesAreaScope
{
    protected function resolveAreaScope(
        string $permission,
        string $areaRoute,
        string $pickerTitle,
    ): Response|RedirectResponse|null {
        $user = Auth::user() ?? abort(401);
        $scope = $user->accessibleAreasForPermission($permission);

        if (! $scope->hasAccess()) {
            abort(403);
        }

        if ($scope->isGlobal) {
            return null;
        }

        if ($scope->areas->count() === 1) {
            return redirect()->route($areaRoute, $scope->areas->first()->id);
        }

        return response()->view('partials.area-picker', [
            'areas' => $scope->areas,
            'route' => $areaRoute,
            'title' => $pickerTitle,
        ]);
    }
}
