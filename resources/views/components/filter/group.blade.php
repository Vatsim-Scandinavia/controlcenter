{{--
    Segmented filter pills for the page header, as used on Training Statistics.

    Wraps any number of <x-filter.item> children. Permission gating stays in the
    caller, since every page filters on its own permission and area source.
--}}
@props(['label' => 'Filter', 'icon' => 'fa-filter'])

<div {{ $attributes->class(['input-group', 'input-group-sm', 'w-auto', 'align-self-center']) }}>
    <span class="input-group-text"><i class="fas {{ $icon }} me-1"></i>{{ $label }}</span>
    {{ $slot }}
</div>
