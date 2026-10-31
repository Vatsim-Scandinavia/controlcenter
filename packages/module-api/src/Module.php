<?php

namespace ControlCenter\ModuleApi;

/**
 * The single entry point implemented by every Control Center module.
 *
 * A module is an ordinary Composer package of type `controlcenter-module`.
 * Its `extra.controlcenter.module` key names the class implementing this
 * interface. Core discovers installed modules, resolves this class from the
 * container (so constructor injection works) and, for every *enabled* module,
 * calls {@see Module::register()} exactly once during boot.
 *
 * Modules are trusted in-process code. The manifest's declared permissions and
 * data-handling entries are transparency for operators, not a sandbox: a module
 * runs with the full privileges of the application.
 */
interface Module
{
    /**
     * Static, declarative metadata describing the module. Read during discovery
     * and shown on the admin page; must not depend on runtime state.
     */
    public function manifest(): ModuleManifest;

    /**
     * Register the module's extension points with core.
     *
     * Called once per request/command lifecycle, only while the module is
     * enabled and compatible with the running Control Center version. Keep this
     * cheap and free of side effects — it runs on every boot, including the
     * sidebar, which renders on nearly every page.
     */
    public function register(Registrar $registrar): void;
}
