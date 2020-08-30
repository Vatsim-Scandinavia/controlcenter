@component('mail::message')

{{-- Greeting --}}
# {{ $greeting }}

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

@slot('subcopy')
For questions regarding your training, contact [{{ $contactMail }}](mailto:{{ $contactMail }})
@endslot

@endcomponent