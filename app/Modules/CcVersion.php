<?php

namespace App\Modules;

use Composer\Semver\Semver;

/**
 * Checks a module's declared Control Center version constraint against the
 * running application version.
 *
 * Uses composer/semver when it is installed (it ships as a transitive Composer
 * dependency) and otherwise falls back to a conservative check that understands
 * the caret, comparison and exact-match forms modules realistically use.
 */
class CcVersion
{
    public static function current(): string
    {
        return (string) config('app.version');
    }

    public static function satisfies(string $constraint, ?string $version = null): bool
    {
        $version = self::normalise($version ?? self::current());
        $constraint = trim($constraint);

        if ($constraint === '' || $constraint === '*') {
            return true;
        }

        if (class_exists(Semver::class)) {
            try {
                return Semver::satisfies($version, $constraint);
            } catch (\Throwable) {
                // Fall through to the built-in check below.
            }
        }

        foreach (preg_split('/\s*\|\|\s*/', $constraint) ?: [] as $clause) {
            if (self::satisfiesClause($clause, $version)) {
                return true;
            }
        }

        return false;
    }

    private static function satisfiesClause(string $clause, string $version): bool
    {
        $parts = preg_split('/\s+/', trim($clause)) ?: [];

        foreach ($parts as $part) {
            if ($part !== '' && ! self::satisfiesComparator($part, $version)) {
                return false;
            }
        }

        return $parts !== [];
    }

    private static function satisfiesComparator(string $comparator, string $version): bool
    {
        if (str_starts_with($comparator, '^')) {
            $base = self::normalise(substr($comparator, 1));
            [$major] = explode('.', $base);
            [$vMajor] = explode('.', $version);

            return $vMajor === $major && version_compare($version, $base, '>=');
        }

        foreach (['>=', '<=', '>', '<', '='] as $op) {
            if (str_starts_with($comparator, $op)) {
                return version_compare($version, self::normalise(substr($comparator, strlen($op))), $op === '=' ? '==' : $op);
            }
        }

        return version_compare($version, self::normalise($comparator), '==');
    }

    private static function normalise(string $version): string
    {
        // Drop any pre-release/build suffix so 7.0.0-beta.4 compares as 7.0.0.
        $version = preg_replace('/[-+].*$/', '', trim($version)) ?? $version;
        $segments = explode('.', $version);

        while (count($segments) < 3) {
            $segments[] = '0';
        }

        return implode('.', array_slice($segments, 0, 3));
    }
}
