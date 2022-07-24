@component('mail::message')

{{-- Greeting --}}
# Hello {{ $firstName }},

{{-- Intro Lines --}}
@foreach ($textLines as $line)
{{ $line }}

@endforeach

{{-- Subcopy --}}
@slot('subcopy')
For questions regarding your endorsement, contact your mentor.
@endslot

@endcomponent