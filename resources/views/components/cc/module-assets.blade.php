@props(['slug', 'type' => 'all'])

@php
    $assets = app(\App\Modules\ModuleAssets::class);
    $showStyles = in_array($type, ['all', 'css'], true);
    $showScripts = in_array($type, ['all', 'js'], true);
@endphp

{{-- Emits <link>/<script> tags for a module's prebuilt assets. Use from the
     `head` slot (type="css") and `scripts` slot (type="js"). --}}
@if($showStyles)
    @foreach($assets->styles($slug) as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
@endif

@if($showScripts)
    @foreach($assets->scripts($slug) as $src)
        <script src="{{ $src }}" defer></script>
    @endforeach
@endif
