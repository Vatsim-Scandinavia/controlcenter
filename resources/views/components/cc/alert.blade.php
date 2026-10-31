@props(['type' => 'info', 'icon' => null])

@php
    $icons = [
        'success' => 'fa-check-circle',
        'danger' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle',
    ];
    $resolvedIcon = $icon ?? ($icons[$type] ?? 'fa-info-circle');
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }} role="alert">
    @if($resolvedIcon)
        <i class="fas fa-lg {{ $resolvedIcon }}"></i>&nbsp;
    @endif
    {{ $slot }}
</div>
