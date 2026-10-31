@props(['action' => '#', 'method' => 'POST'])

@php
    $verb = strtoupper($method);
    $spoof = in_array($verb, ['GET', 'POST'], true) ? null : $verb;
    $formMethod = $spoof ? 'POST' : $verb;
@endphp

<form action="{{ $action }}" method="{{ $formMethod }}" {{ $attributes }}>
    @if($formMethod !== 'GET')
        @csrf
    @endif
    @if($spoof)
        @method($spoof)
    @endif

    {{ $slot }}
</form>
