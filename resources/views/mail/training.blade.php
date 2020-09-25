@component('mail::message')

{{-- Greeting --}}
# Hello {{ $firstName }},

{{-- Intro Lines --}}
@foreach ($textLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionUrl)
@component('mail::button', ['url' => $actionUrl, 'color' => $actionColor])
{{ $actionText }}
@endcomponent
@endisset

{{-- Subcopy --}}
@isset($contactMail)
@slot('subcopy')
For questions regarding your training, contact [{{ $contactMail }}](mailto:{{ $contactMail }})
@endslot
@endisset

@endcomponent