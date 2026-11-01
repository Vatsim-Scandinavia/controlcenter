@props(['name', 'label' => null, 'options' => [], 'value' => null, 'help' => null, 'required' => false])

<div class="mb-4">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        @required($required)
        {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if($help)
        <small class="form-text">{{ $help }}</small>
    @endif

    @error($name)
        <span class="text-danger d-block">{{ $message }}</span>
    @enderror
</div>
