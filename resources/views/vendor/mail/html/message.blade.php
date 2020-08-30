@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
Vatsim Scandinavia Control Center
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}


@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
![Logo]({{ asset('images/vatsca-logo-email.png') }})\
[Change your e-mail settings here]({{ route('user.settings') }})
@endcomponent
@endslot
@endcomponent
