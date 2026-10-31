<?php

namespace App\Modules;

/**
 * Table-naming convention for module-owned database tables.
 *
 * Modules MUST create their own tables under the `modules_<slug>_` prefix and
 * MUST NOT alter core tables. Keeping every module table behind a predictable,
 * slug-scoped prefix means a module's storage can be identified, backed up or
 * dropped without guesswork, and two modules can never collide on a table name.
 *
 * Example: a module with slug `roster` owning a `shifts` table names it
 * `modules_roster_shifts`.
 */
class ModuleTables
{
    /**
     * The table-name prefix every table owned by the given module must use.
     */
    public static function prefix(string $slug): string
    {
        return "modules_{$slug}_";
    }

    /**
     * Whether the given table name belongs to the given module (i.e. uses the
     * module's prefix). Useful for assertions in tests that guard a module
     * against touching core tables.
     */
    public static function belongsToModule(string $table, string $slug): bool
    {
        return str_starts_with($table, self::prefix($slug));
    }
}
