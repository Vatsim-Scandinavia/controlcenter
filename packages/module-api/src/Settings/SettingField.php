<?php

namespace ControlCenter\ModuleApi\Settings;

use InvalidArgumentException;

/**
 * One field in a module's settings schema.
 *
 * Core renders the field, validates incoming input against {@see $rules} and
 * persists it. Fields marked {@see $secret} are stored encrypted and never
 * echoed back to the form or written to logs — the form shows only whether a
 * value is set.
 */
final class SettingField
{
    public const array TYPES = ['text', 'textarea', 'number', 'boolean', 'select', 'password'];

    /**
     * @param  string  $key  Short key, unique within the schema. Persisted under `modules.<slug>.<key>`.
     * @param  string  $label  Human-readable label.
     * @param  string  $type  One of {@see SettingField::TYPES}.
     * @param  array<int, string>  $rules  Laravel validation rules applied to the submitted value.
     * @param  string|null  $help  Optional help text shown under the field.
     * @param  mixed  $default  Default value when unset.
     * @param  array<string|int, string>  $options  Options for `select` fields, as value => label.
     * @param  bool  $secret  When true, the value is encrypted at rest and never returned to the UI in cleartext.
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type = 'text',
        public array $rules = [],
        public ?string $help = null,
        public mixed $default = null,
        public array $options = [],
        public bool $secret = false,
    ) {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Unknown setting field type [{$type}].");
        }

        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $key)) {
            throw new InvalidArgumentException("Invalid setting field key [{$key}].");
        }
    }
}
