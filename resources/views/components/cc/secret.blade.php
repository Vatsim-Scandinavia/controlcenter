@props(['name', 'label' => null, 'isSet' => false, 'help' => null])

{{-- Secret field: the stored value is never echoed back. We only report whether
     a value is set and let the operator enter a new value to replace it. --}}
<div class="mb-4">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif

    <input
        type="password"
        name="{{ $name }}"
        id="{{ $name }}"
        autocomplete="new-password"
        placeholder="{{ $isSet ? 'Set — leave blank to keep the current value' : 'Not set' }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>

    <small class="form-text d-block">
        @if($isSet)
            <span class="badge bg-success">Set</span> Enter a new value to replace it.
        @else
            <span class="badge bg-secondary">Not set</span>
        @endif
        @if($help) {{ $help }}@endif
    </small>

    @error($name)
        <span class="text-danger d-block">{{ $message }}</span>
    @enderror
</div>
