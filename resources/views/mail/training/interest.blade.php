@component('mail::message')

{{-- Greeting --}}
# {{ $greeting }}

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@component('mail::button', ['url' => $actionUrl, 'color' => 'success'])
Confirm Interest
@endcomponent

{{-- Subcopy --}}
@isset($actionUrl)
@slot('subcopy')
@lang(
    "If you’re having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
        'actionText' => $actionText,
    ]
) <span class="break-all">[{{ $actionUrl }}]({{ $actionUrl }})</span>
@endslot
@endisset
@endcomponent
