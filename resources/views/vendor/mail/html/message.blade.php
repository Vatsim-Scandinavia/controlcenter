@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ Config::get('app.owner') }}<br><span style="font-weight: 400">Control Center</span>
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
[![Logo]({{ asset('images/logos/vat'.mb_strtolower(Config::get('app.owner_short')).'-email.png') }})]({{ Setting::get("linkHome") }})\
[Change your e-mail settings here]({{ route('user.settings') }})
@endcomponent
@endslot
@endcomponent
