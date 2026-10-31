<?php

namespace ControlCenter\ModuleApi\Privacy;

/**
 * Contract a module implements when it stores data about users.
 *
 * Control Center's `user:delete` command invokes the handler of **every
 * installed module — enabled or not** — because a disabled module's data still
 * exists on disk. A module that stores user data must declare a handler and
 * list that data in its manifest's `dataHandling`.
 *
 * Users are identified by their VATSIM CID so the contract stays decoupled from
 * Control Center's Eloquent models.
 */
interface PrivacyHandler
{
    /**
     * Plain-language description of the user data this module stores. Shown to
     * operators alongside the module.
     */
    public function describe(): string;

    /**
     * Return the module's data for the given user, ready to be merged into a
     * data-export bundle.
     *
     * @return array<string, mixed>
     */
    public function export(int $cid): array;

    /**
     * Irreversibly scrub personally identifying information for the given user
     * while keeping any records that must survive for integrity (anonymised).
     */
    public function pseudonymise(int $cid): void;

    /**
     * Permanently delete all of the module's data for the given user.
     */
    public function delete(int $cid): void;
}
