<?php

namespace App\Modules;

use App\Models\ModuleUserSetting;
use ControlCenter\ModuleApi\Settings\SettingField;
use ControlCenter\ModuleApi\Settings\SettingsSchema;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Reads and writes a module's per-user settings (declared via
 * Registrar::userSettings()) under the same `modules.<slug>.<key>` namespace as
 * global module settings, scoped to a user id. Secret fields get the same
 * treatment as global module settings: encrypted at rest, never returned to the
 * UI in cleartext nor logged.
 */
class ModuleUserSettings
{
    public function prefix(): string
    {
        return (string) config('modules.settings_prefix', 'modules');
    }

    public function key(string $slug, string $key): string
    {
        return "{$this->prefix()}.{$slug}.{$key}";
    }

    private function row(int $userId, string $fullKey): ?ModuleUserSetting
    {
        return ModuleUserSetting::query()
            ->where('user_id', $userId)
            ->where('key', $fullKey)
            ->first();
    }

    public function get(int $userId, string $slug, string $key, mixed $default = null): mixed
    {
        return $this->row($userId, $this->key($slug, $key))?->value ?? $default;
    }

    public function set(int $userId, string $slug, string $key, mixed $value): void
    {
        ModuleUserSetting::query()->updateOrCreate(
            ['user_id' => $userId, 'key' => $this->key($slug, $key)],
            ['value' => $value === null ? null : (string) $value],
        );
    }

    public function isSet(int $userId, string $slug, string $key): bool
    {
        $value = $this->row($userId, $this->key($slug, $key))?->value;

        return $value !== null && $value !== '';
    }

    public function getSecret(int $userId, string $slug, string $key): ?string
    {
        $stored = $this->row($userId, $this->key($slug, $key))?->value;

        if ($stored === null || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString((string) $stored);
        } catch (DecryptException) {
            return null;
        }
    }

    public function setSecret(int $userId, string $slug, string $key, string $value): void
    {
        $this->set($userId, $slug, $key, Crypt::encryptString($value));
    }

    /**
     * Persist already-validated input for every field in the schema. Secret
     * fields left blank keep their existing value.
     *
     * @param  array<string, mixed>  $data
     */
    public function persistSchema(int $userId, string $slug, SettingsSchema $schema, array $data): void
    {
        foreach ($schema->fields() as $field) {
            $key = $field->key;

            if ($field->secret) {
                $incoming = $data[$key] ?? null;

                if ($incoming !== null && $incoming !== '') {
                    $this->setSecret($userId, $slug, $key, (string) $incoming);
                }

                continue;
            }

            if ($field->type === 'boolean') {
                $this->set($userId, $slug, $key, (bool) ($data[$key] ?? false) ? '1' : '0');

                continue;
            }

            $this->set($userId, $slug, $key, $data[$key] ?? null);
        }
    }

    /**
     * Current non-secret values keyed by field key, for pre-filling the form.
     *
     * @return array<string, mixed>
     */
    public function values(int $userId, string $slug, SettingsSchema $schema): array
    {
        $values = [];

        foreach ($schema->fields() as $field) {
            if ($field->secret) {
                continue;
            }

            $values[$field->key] = $this->get($userId, $slug, $field->key, $field->default);
        }

        return $values;
    }

    /**
     * Whether each secret field in the schema currently has a stored value.
     *
     * @return array<string, bool>
     */
    public function secretState(int $userId, string $slug, SettingsSchema $schema): array
    {
        $state = [];

        foreach ($schema->fields() as $field) {
            if ($field->secret) {
                $state[$field->key] = $this->isSet($userId, $slug, $field->key);
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
     * The keys of every secret field, kept out of flashed old input on failure.
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
