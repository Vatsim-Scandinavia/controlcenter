<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\LogName;
use App\Http\Controllers\Controller;
use App\Modules\ModuleRegistry;
use App\Modules\ModuleSettings;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin surface for inspecting installed modules and toggling them at runtime.
 * Guarded by the dedicated `system.modules.manage` permission.
 */
class ModuleController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        $this->authorize('system.modules.manage');

        return view('admin.modules', [
            'modules' => $registry->discovered(),
            'safeMode' => $registry->safeMode(),
            'moduleErrors' => $registry->errors(),
            'configurable' => $this->configurableSlugs($registry),
        ]);
    }

    /**
     * Render a module's admin settings schema. Core owns the form, validation
     * and persistence; the module only declares the schema.
     */
    public function settings(ModuleRegistry $registry, ModuleSettings $settings, string $slug): View|RedirectResponse
    {
        $this->authorize('system.modules.manage');

        $module = $registry->discovered()[$slug] ?? null;

        if ($module === null) {
            return redirect()->route('admin.modules')->with('error', "Module [{$slug}] is not installed.");
        }

        $schema = $registry->registrarFor($slug)?->adminSettings;

        if ($schema === null || $schema->isEmpty()) {
            return redirect()->route('admin.modules')->with('error', "Module [{$slug}] has no settings.");
        }

        return view('admin.module-settings', [
            'module' => $module,
            'slug' => $slug,
            'schema' => $schema,
            'values' => $settings->values($slug, $schema),
            'secretState' => $settings->secretState($slug, $schema),
        ]);
    }

    public function updateSettings(Request $request, ModuleRegistry $registry, ModuleSettings $settings, string $slug): RedirectResponse
    {
        $this->authorize('system.modules.manage');

        $schema = $registry->registrarFor($slug)?->adminSettings;

        if ($schema === null || $schema->isEmpty()) {
            return redirect()->route('admin.modules')->with('error', "Module [{$slug}] has no settings.");
        }

        $validated = $request->validate($settings->rules($schema));

        $settings->persistSchema($slug, $schema, $validated);
        ActivityLogService::danger(LogName::Other, "Module settings updated: {$slug}");

        return redirect()->route('admin.modules.settings', $slug)->with('success', 'Module settings saved.');
    }

    public function enable(Request $request, ModuleRegistry $registry, string $slug): RedirectResponse
    {
        $this->authorize('system.modules.manage');

        $module = $registry->discovered()[$slug] ?? null;

        if ($module === null) {
            return redirect()->route('admin.modules')->with('error', "Module [{$slug}] is not installed.");
        }

        if (! $module->compatible) {
            return redirect()->route('admin.modules')->with('error', $module->incompatibilityReason());
        }

        $registry->enable($slug);
        ActivityLogService::danger(LogName::Other, "Module enabled: {$slug}");

        return redirect()->route('admin.modules')->with('success', "Module [{$slug}] enabled.");
    }

    public function disable(Request $request, ModuleRegistry $registry, string $slug): RedirectResponse
    {
        $this->authorize('system.modules.manage');

        if (! isset($registry->discovered()[$slug])) {
            return redirect()->route('admin.modules')->with('error', "Module [{$slug}] is not installed.");
        }

        $registry->disable($slug);
        ActivityLogService::danger(LogName::Other, "Module disabled: {$slug}");

        return redirect()->route('admin.modules')->with('success', "Module [{$slug}] disabled.");
    }

    /**
     * Slugs of enabled modules that declare an admin settings schema, so the
     * modules page can link to their settings.
     *
     * @return array<int, string>
     */
    private function configurableSlugs(ModuleRegistry $registry): array
    {
        $slugs = [];

        foreach (array_keys($registry->booted()) as $slug) {
            $schema = $registry->registrarFor($slug)?->adminSettings;

            if ($schema !== null && ! $schema->isEmpty()) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }
}
