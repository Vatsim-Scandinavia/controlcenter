@component('mail::message')

{{-- Greeting --}}
# Hello,

{{-- Intro Lines --}}
@foreach ($textLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@if($actionUrl !== null)
@component('mail::button', ['url' => $actionUrl, 'color' => 'primary'])
{{ $actionText }}
@endcomponent
@endif

{{-- Subcopy --}}
@slot('subcopy')
This is an automatically generated notice. If you think you received this by error, contact the staff.
@endslot

@endcomponent