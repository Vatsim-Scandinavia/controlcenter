<?php

namespace App\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;

/**
 * A module found during discovery, together with everything core needs to
 * reason about it before deciding whether to boot it.
 */
final class DiscoveredModule
{
    /**
     * @param  class-string<Module>  $class
     */
    public function __construct(
        public string $class,
        public ModuleManifest $manifest,
        public bool $enabled,
        public bool $compatible,
        public string $source,
    ) {}

    public function slug(): string
    {
        return $this->manifest->slug;
    }

    /**
     * Whether this module should be booted: installed, enabled and compatible.
     */
    public function shouldBoot(): bool
    {
        return $this->enabled && $this->compatible;
    }

    public function incompatibilityReason(): ?string
    {
        if ($this->compatible) {
            return null;
        }

        return "Module [{$this->slug()}] requires Control Center {$this->manifest->ccVersion}, but this instance is "
            . CcVersion::current() . '.';
    }
}
