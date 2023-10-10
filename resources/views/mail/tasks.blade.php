@component('mail::message')

{{-- Greeting --}}
# Hello {{ $firstName }},

{{-- Intro Lines --}}
@foreach ($textLines as $line)
{{ $line }}
@endforeach

{{-- Action Button --}}
@component('mail::button', ['url' => $actionUrl, 'color' => 'primary'])
    See tasks
@endcomponent

{{-- Subcopy --}}
@slot('subcopy')
This is an automatically generated notice.
@endslot

@endcomponent