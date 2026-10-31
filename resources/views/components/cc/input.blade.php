@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])

<div class="mb-4">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        @required($required)
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>

    @if($help)
        <small class="form-text">{{ $help }}</small>
    @endif

    @error($name)
        <span class="text-danger d-block">{{ $message }}</span>
    @enderror
</div>
