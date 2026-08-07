{{-- Renders a single activity-change value: linked when a URL is present, plain text otherwise. --}}
@if(! empty($value['url']))
    <a href="{{ $value['url'] }}">{{ $value['text'] }}</a>
@else
    {{ $value['text'] }}
@endif
