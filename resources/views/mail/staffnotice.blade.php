@component('mail::message')

{{-- Greeting --}}
# Hello,

{{-- Intro Lines --}}
@foreach ($textLines as $line)
{{ $line }}

@endforeach

{{-- Subcopy --}}
@slot('subcopy')
This is an automatically generated notice. If you think you received this by error, contact the staff.
@endslot

@endcomponent