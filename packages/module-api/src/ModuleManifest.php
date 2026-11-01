<?php

namespace ControlCenter\ModuleApi;

use ControlCenter\ModuleApi\Privacy\PrivacyHandler;
use InvalidArgumentException;

/**
 * Immutable, declarative description of a module.
 *
 * Everything here is transparency for operators rather than an enforcement
 * boundary. The declared permissions and data-handling notes tell an operator
 * what the module intends to touch; they do not constrain what its code can do.
 */
final class ModuleManifest
{
    /**
     * @param  string  $slug  Stable, lowercase kebab-case identifier, unique across installed modules. Namespaces the module's permissions, routes, settings and tables.
     * @param  string  $name  Human-readable name shown in the admin UI.
     * @param  string  $version  The module's own semantic version.
     * @param  string  $ccVersion  Composer version constraint against Control Center's `app.version` (e.g. `^7.0`). Incompatible modules are not booted.
     * @param  string  $description  One-paragraph summary shown to operators.
     * @param  string|null  $author  Optional author/vendor name.
     * @param  array<string, string>  $permissions  Declared permissions the module registers, as `modules.<slug>.*` => human description. Transparency only.
     * @param  array<int, string>  $dataHandling  Plain-language notes on what user data the module stores, for the privacy/export flow.
     * @param  class-string<PrivacyHandler>|null  $privacyHandler  The module's privacy handler, declared here (not in register()) so it is available for a departing user's data even while the module is disabled.
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $version,
        public string $ccVersion,
        public string $description = '',
        public ?string $author = null,
        public array $permissions = [],
        public array $dataHandling = [],
        public ?string $privacyHandler = null,
    ) {
        if (! preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException("Invalid module slug [{$slug}]. Use lowercase kebab-case, e.g. 'my-module'.");
        }

        foreach (array_keys($permissions) as $permission) {
            if (! str_starts_with((string) $permission, "modules.{$slug}.")) {
                throw new InvalidArgumentException("Declared permission [{$permission}] must live under the module namespace [modules.{$slug}.].");
            }
        }
    }

    /**
     * Build a manifest from a decoded `module.json` / composer `extra` array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        foreach (['slug', 'name', 'version', 'ccVersion'] as $required) {
            if (! isset($data[$required]) || ! is_string($data[$required])) {
                throw new InvalidArgumentException("Module manifest is missing required string key [{$required}].");
            }
        }

        return new self(
            slug: $data['slug'],
            name: $data['name'],
            version: $data['version'],
            ccVersion: $data['ccVersion'],
            description: $data['description'] ?? '',
            author: $data['author'] ?? null,
            permissions: $data['permissions'] ?? [],
            dataHandling: $data['dataHandling'] ?? [],
            privacyHandler: $data['privacyHandler'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'version' => $this->version,
            'ccVersion' => $this->ccVersion,
            'description' => $this->description,
            'author' => $this->author,
            'permissions' => $this->permissions,
            'dataHandling' => $this->dataHandling,
            'privacyHandler' => $this->privacyHandler,
        ];
    }
}
