<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\LogName;
use App\Http\Controllers\Controller;
use App\Modules\ModuleRegistry;
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
        ]);
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
}
