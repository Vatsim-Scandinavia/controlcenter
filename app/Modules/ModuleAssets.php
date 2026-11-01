<?php

namespace App\Modules;

use Throwable;

/**
 * Resolves a module's prebuilt front-end assets.
 *
 * The runtime image has no Node, so modules ship assets already built. A module
 * publishes its built files to `public/modules/<slug>/` with a small
 * `manifest.json` of the shape:
 *
 *     { "styles": ["app-abc123.css"], "scripts": ["app-abc123.js"] }
 *
 * Paths are resolved relative to that directory and turned into public URLs.
 * Missing or malformed manifests resolve to nothing rather than erroring.
 */
class ModuleAssets
{
    /**
     * @return array{styles: array<int, string>, scripts: array<int, string>}
     */
    public function manifest(string $slug): array
    {
        $empty = ['styles' => [], 'scripts' => []];
        $path = public_path("modules/{$slug}/manifest.json");

        if (! is_file($path)) {
            return $empty;
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return $empty;
        }

        return [
            'styles' => array_values(array_filter((array) ($data['styles'] ?? []), 'is_string')),
            'scripts' => array_values(array_filter((array) ($data['scripts'] ?? []), 'is_string')),
        ];
    }

    /**
     * Public URLs for the module's built stylesheets.
     *
     * @return array<int, string>
     */
    public function styles(string $slug): array
    {
        return array_map(fn (string $file): string => asset("modules/{$slug}/{$file}"), $this->manifest($slug)['styles']);
    }

    /**
     * Public URLs for the module's built scripts.
     *
     * @return array<int, string>
     */
    public function scripts(string $slug): array
    {
        return array_map(fn (string $file): string => asset("modules/{$slug}/{$file}"), $this->manifest($slug)['scripts']);
    }
}
