@php
    $registry = app(\App\Modules\ModuleRegistry::class);
    $userSettings = app(\App\Modules\ModuleUserSettings::class);
    $currentUserId = (int) auth()->id();
@endphp

@foreach($registry->booted() as $slug => $registrar)
    @php($schema = $registrar->userSettings)
    @continue($schema === null || $schema->isEmpty())
    @php($module = $registry->discovered()[$slug] ?? null)
    @php($values = $userSettings->values($currentUserId, $slug, $schema))
    @php($secretState = $userSettings->secretState($currentUserId, $slug, $schema))
    @php($bag = $errors->getBag("module_{$slug}"))

    <x-cc.form :action="route('user.settings.modules.store', $slug)">
        <x-cc.card :title="($module?->manifest->name ?? $slug) . ' Settings'">
            @foreach($schema->fields() as $field)
                @if($field->secret)
                    <x-cc.secret :name="$field->key" :label="$field->label" :isSet="$secretState[$field->key] ?? false" :help="$field->help" />
                @elseif($field->type === 'boolean')
                    <x-cc.checkbox :name="$field->key" :label="$field->label" :checked="(bool) old($field->key, $values[$field->key] ?? false)" :help="$field->help" />
                @elseif($field->type === 'textarea')
                    <x-cc.textarea :name="$field->key" :label="$field->label" :value="old($field->key, $values[$field->key] ?? $field->default)" :help="$field->help" />
                @elseif($field->type === 'select')
                    <x-cc.select :name="$field->key" :label="$field->label" :options="$field->options" :value="old($field->key, $values[$field->key] ?? $field->default)" :help="$field->help" />
                @else
                    <x-cc.input :name="$field->key" :label="$field->label" :type="$field->type === 'number' ? 'number' : 'text'" :value="old($field->key, $values[$field->key] ?? $field->default)" :help="$field->help" />
                @endif

                @if($bag->has($field->key))
                    <div class="text-danger fs-sm">{{ $bag->first($field->key) }}</div>
                @endif
            @endforeach

            <button class="btn btn-success mt-2" type="submit">Save</button>
        </x-cc.card>
    </x-cc.form>
@endforeach
