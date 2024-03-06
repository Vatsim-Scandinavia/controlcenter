@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.owner_name') }}<br><span style="font-weight: 400">Control Center</span>
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
[![Logo]({{ asset('images/logos/'.Config::get('app.logo_mail')) }})]({{ Setting::get("linkHome") }})\
[Change your e-mail settings here]({{ route('user.settings') }})
@endcomponent
@endslot
@endcomponent
