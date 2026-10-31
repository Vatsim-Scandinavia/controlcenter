@props(['name', 'label' => null, 'value' => null, 'help' => null, 'required' => false, 'rows' => 3])

<div class="mb-4">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        @required($required)
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>

    @if($help)
        <small class="form-text">{{ $help }}</small>
    @endif

    @error($name)
        <span class="text-danger d-block">{{ $message }}</span>
    @enderror
</div>
