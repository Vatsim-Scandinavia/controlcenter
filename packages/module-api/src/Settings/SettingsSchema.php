<?php

namespace ControlCenter\ModuleApi\Settings;

/**
 * An ordered collection of {@see SettingField}s a module exposes on its
 * settings page. Core renders and validates the schema, so modules never write
 * their own settings form or validation rules.
 */
final class SettingsSchema
{
    /**
     * @var array<int, SettingField>
     */
    private array $fields = [];

    public function add(SettingField $field): static
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Convenience factory for a plain text field.
     *
     * @param  array<int, string>  $rules
     */
    public function text(string $key, string $label, array $rules = [], ?string $help = null): static
    {
        return $this->add(new SettingField($key, $label, 'text', $rules, $help));
    }

    /**
     * Convenience factory for an encrypted secret field.
     *
     * @param  array<int, string>  $rules
     */
    public function secret(string $key, string $label, array $rules = [], ?string $help = null): static
    {
        return $this->add(new SettingField($key, $label, 'password', $rules, $help, secret: true));
    }

    /**
     * Convenience factory for a boolean toggle.
     */
    public function boolean(string $key, string $label, ?string $help = null): static
    {
        return $this->add(new SettingField($key, $label, 'boolean', [], $help, default: false));
    }

    /**
     * @return array<int, SettingField>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function isEmpty(): bool
    {
        return $this->fields === [];
    }
}
