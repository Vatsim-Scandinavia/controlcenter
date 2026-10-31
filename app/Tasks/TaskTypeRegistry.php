<?php

namespace App\Tasks;

use App\Modules\ModuleRegistry;
use App\Tasks\Types\Custom;
use App\Tasks\Types\MissingType;
use App\Tasks\Types\RatingUpgrade;
use App\Tasks\Types\SoloEndorsement;
use App\Tasks\Types\TheoreticalExam;
use App\Tasks\Types\Types;

/**
 * Resolves task types by a stable string key rather than by class name.
 *
 * Tasks store this key in their `type` column. Core types have fixed keys;
 * modules contribute additional keys while enabled. A stored key whose type is
 * no longer available (a module was disabled or removed) resolves to a
 * {@see MissingType} so task lists keep rendering instead of throwing.
 */
class TaskTypeRegistry
{
    /**
     * Core task types, keyed by their stable key. The key is persisted, so it
     * must never change once shipped.
     *
     * @var array<string, class-string<Types>>
     */
    private const CORE = [
        Custom::KEY => Custom::class,
        RatingUpgrade::KEY => RatingUpgrade::class,
        SoloEndorsement::KEY => SoloEndorsement::class,
        TheoreticalExam::KEY => TheoreticalExam::class,
    ];

    public function __construct(private ModuleRegistry $modules) {}

    /**
     * Every available task type key mapped to its class, core plus the types
     * registered by enabled modules.
     *
     * @return array<string, class-string<Types>>
     */
    public function map(): array
    {
        return array_merge(self::CORE, $this->modules->taskTypes());
    }

    /**
     * Instantiate the task type for a stored value. Falls back to a safe
     * placeholder when the key is unknown. For backwards compatibility a stored
     * legacy class name still resolves to that class.
     */
    public function make(string $stored): Types
    {
        $map = $this->map();

        if (isset($map[$stored])) {
            return app($map[$stored]);
        }

        if (class_exists($stored) && is_subclass_of($stored, Types::class)) {
            return app($stored);
        }

        return new MissingType($stored);
    }

    public function has(string $key): bool
    {
        return isset($this->map()[$key]);
    }

    /**
     * The stable key for a given task type class, or null if it is not
     * registered.
     *
     * @param  class-string<Types>  $class
     */
    public function keyFor(string $class): ?string
    {
        $key = array_search($class, $this->map(), true);

        return $key === false ? null : $key;
    }

    /**
     * All available task types as key => instance, for building menus and
     * validation. The placeholder type is never included.
     *
     * @return array<string, Types>
     */
    public function all(): array
    {
        $types = [];

        foreach ($this->map() as $key => $class) {
            $types[$key] = app($class);
        }

        return $types;
    }
}
