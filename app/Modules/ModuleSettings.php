<?php

namespace App\Modules;

use anlutro\LaravelSettings\Facade as Setting;
use ControlCenter\ModuleApi\Settings\SettingField;
use ControlCenter\ModuleApi\Settings\SettingsSchema;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Reads and writes a module's slug-scoped settings under the
 * `modules.<slug>.<key>` namespace. Secret fields are encrypted at rest with
 * Laravel's {@see Crypt} and are never returned to the UI in cleartext nor
 * logged — callers only learn whether a value is set.
 */
class ModuleSettings
{
    public function prefix(): string
    {
        return (string) config('modules.settings_prefix', 'modules');
    }

    public function key(string $slug, string $key): string
    {
        return "{$this->prefix()}.{$slug}.{$key}";
    }

    public function get(string $slug, string $key, mixed $default = null): mixed
    {
        return Setting::get($this->key($slug, $key), $default);
    }

    public function set(string $slug, string $key, mixed $value): void
    {
        // The underlying store does not accept null; mirror core by storing false.
        Setting::set($this->key($slug, $key), $value ?? false);
    }

    public function forget(string $slug, string $key): void
    {
        Setting::forget($this->key($slug, $key));
    }

    /**
     * Whether a (secret or plain) value is stored and non-empty.
     */
    public function isSet(string $slug, string $key): bool
    {
        $value = Setting::get($this->key($slug, $key));

        return $value !== null && $value !== '' && $value !== false;
    }

    /**
     * Decrypt and return a secret, or null when unset or undecryptable.
     */
    public function getSecret(string $slug, string $key): ?string
    {
        $stored = Setting::get($this->key($slug, $key));

        if ($stored === null || $stored === '' || $stored === false) {
            return null;
        }

        try {
            return Crypt::decryptString((string) $stored);
        } catch (DecryptException) {
            return null;
        }
    }

    public function setSecret(string $slug, string $key, string $value): void
    {
        Setting::set($this->key($slug, $key), Crypt::encryptString($value));
    }

    public function save(): void
    {
        Setting::save();
    }

    /**
     * Persist already-validated input for every field in the schema. Secret
     * fields left blank keep their existing value; booleans coerce to a stored
     * bool; everything else is stored as-is.
     *
     * @param  array<string, mixed>  $data
     */
    public function persistSchema(string $slug, SettingsSchema $schema, array $data): void
    {
        foreach ($schema->fields() as $field) {
            $key = $field->key;

            if ($field->secret) {
                $incoming = $data[$key] ?? null;

                if ($incoming !== null && $incoming !== '') {
                    $this->setSecret($slug, $key, (string) $incoming);
                }

                continue;
            }

            if ($field->type === 'boolean') {
                $this->set($slug, $key, (bool) ($data[$key] ?? false));

                continue;
            }

            $this->set($slug, $key, $data[$key] ?? null);
        }

        $this->save();
    }

    /**
     * Current non-secret values keyed by field key, for pre-filling the form.
     *
     * @return array<string, mixed>
     */
    public function values(string $slug, SettingsSchema $schema): array
    {
        $values = [];

        foreach ($schema->fields() as $field) {
            if ($field->secret) {
                continue;
            }

            $values[$field->key] = $this->get($slug, $field->key, $field->default);
        }

        return $values;
    }

    /**
     * Whether each secret field in the schema currently has a stored value.
     *
     * @return array<string, bool>
     */
    public function secretState(string $slug, SettingsSchema $schema): array
    {
        $state = [];

        foreach ($schema->fields() as $field) {
            if ($field->secret) {
                $state[$field->key] = $this->isSet($slug, $field->key);
            }
        }

        return $state;
    }

    /**
     * Validation rules for every field in the schema, keyed by field key.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(SettingsSchema $schema): array
    {
        return collect($schema->fields())
            ->mapWithKeys(fn (SettingField $field): array => [$field->key => $field->rules])
            ->all();
    }

    /**
     * The keys of every secret field in the schema. Used to keep secrets out of
     * flashed old input when validation fails.
     *
     * @return array<int, string>
     */
    public function secretKeys(SettingsSchema $schema): array
    {
        return collect($schema->fields())
            ->filter(fn (SettingField $field): bool => $field->secret)
            ->map(fn (SettingField $field): string => $field->key)
            ->values()
            ->all();
    }
}
