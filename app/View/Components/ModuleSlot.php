<?php

namespace App\View\Components;

use App\Modules\ModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;
use Throwable;

/**
 * Renders every module-registered view for a named UI slot.
 *
 * Each entry is authorized through the gate (an entry with no permission shows
 * to any authenticated user) and rendered in isolation: a slot view that throws
 * is reported and skipped so one misbehaving module can never break the page.
 */
class ModuleSlot extends Component
{
    /**
     * @param  array<string, mixed>  $data  Contextual data merged into every slot view (e.g. the user being viewed).
     */
    public function __construct(
        public string $name,
        public array $data = [],
    ) {}

    public function render(): View
    {
        return view('components.module-slot', [
            'fragments' => $this->fragments(),
        ]);
    }

    /**
     * Rendered HTML for each authorized, successfully rendered slot entry.
     *
     * @return array<int, string>
     */
    private function fragments(): array
    {
        /** @var ModuleRegistry $registry */
        $registry = app(ModuleRegistry::class);
        $fragments = [];

        foreach ($registry->slot($this->name) as $entry) {
            if (! $this->authorized($entry['permission'])) {
                continue;
            }

            try {
                $fragments[] = view($entry['view'], array_merge($entry['data'], $this->data))->render();
            } catch (Throwable $e) {
                $this->report($entry, $e);
            }
        }

        return $fragments;
    }

    private function authorized(?string $permission): bool
    {
        if ($permission === null) {
            return auth()->check();
        }

        return Gate::allows($permission);
    }

    /**
     * @param  array{slug: string, slot: string, view: string, permission: ?string, data: array<string, mixed>}  $entry
     */
    private function report(array $entry, Throwable $e): void
    {
        Log::error(
            "Module slot [{$entry['slug']}] failed to render view [{$entry['view']}] for slot [{$entry['slot']}]: {$e->getMessage()}",
            ['exception' => $e, 'module' => $entry['slug'], 'slot' => $entry['slot']],
        );

        if (app()->bound('sentry')) {
            app('sentry')->captureException($e);
        }
    }
}
