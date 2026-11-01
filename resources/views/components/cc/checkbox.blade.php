@props(['name', 'label' => null, 'checked' => false, 'help' => null])

<div class="form-check mb-4">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $name }}"
        value="1"
        @checked(old($name, $checked))
        {{ $attributes->class(['form-check-input', 'is-invalid' => $errors->has($name)]) }}>

    @if($label)
        <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
    @endif

    @if($help)
        <small class="form-text d-block">{{ $help }}</small>
    @endif

    @error($name)
        <span class="text-danger d-block">{{ $message }}</span>
    @enderror
</div>
