<?php

namespace App\Http\Controllers;

use App\Modules\ModuleRegistry;
use App\Modules\ModuleUserSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Persists the current user's values for a module's per-user settings schema
 * (declared via Registrar::userSettings()). Core owns the form, validation and
 * persistence; the module only declares the schema. Secret fields are encrypted
 * and never flashed back, exactly as for admin module settings.
 */
class ModuleUserSettingController extends Controller
{
    public function update(Request $request, ModuleRegistry $registry, ModuleUserSettings $settings, string $slug): RedirectResponse
    {
        // A disabled module has no settings to persist.
        if (! $registry->isEnabled($slug)) {
            abort(404);
        }

        $schema = $registry->registrarFor($slug)?->userSettings;

        if ($schema === null || $schema->isEmpty()) {
            abort(404);
        }

        $validator = Validator::make($request->all(), $settings->rules($schema));

        if ($validator->fails()) {
            return redirect()
                ->route('user.settings')
                ->withErrors($validator, "module_{$slug}")
                ->withInput($request->except($settings->secretKeys($schema)));
        }

        $settings->persistSchema((int) Auth::id(), $slug, $schema, $validator->validated());

        return redirect()->route('user.settings')->with('success', 'Your settings were saved.');
    }
}
