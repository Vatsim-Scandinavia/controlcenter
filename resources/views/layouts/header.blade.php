<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>@yield('title', 'Home') | {{ config('app.name') }}</title>

{{-- Inline theme script to prevent flash of wrong theme --}}
{{-- Only applies explicit (non-system) preferences synchronously. All other logic in theme.js --}}
<script>
(function() {
    function read(key) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            return null; // storage blocked: fall back to the system setting
        }
    }

    function valid(preference) {
        return (preference === 'light' || preference === 'dark' || preference === 'system') ? preference : null;
    }

    // The signed-in member's unconfirmed toggle choice, if they left one.
    function pending(userId) {
        try {
            var choice = JSON.parse(read('user_theme_pending'));
            return choice && choice.user === userId ? valid(choice.preference) : null;
        } catch (e) {
            return null;
        }
    }

    // Same order as theme.js: for a signed-in member, a toggle choice the
    // server has not confirmed yet, then the saved preference; for guests,
    // this browser's stored preference.
    var userId = document.documentElement.getAttribute('data-user-id');
    var storedPreference = userId ? valid(document.documentElement.getAttribute('data-user-theme')) : null;
    if (storedPreference) {
        storedPreference = pending(userId) || storedPreference;
    } else {
        storedPreference = valid(read('user_theme_preference'));
    }

    // Resolve to the effective (light/dark) theme. 'system' and unknown
    // values are watched by theme.js after load; here we just detect once
    // to avoid FOUC.
    var effective = (storedPreference === 'light' || storedPreference === 'dark')
        ? storedPreference
        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    // data-bs-theme mirrors data-theme so Bootstrap's dark cascade applies.
    document.documentElement.setAttribute('data-theme', effective);
    document.documentElement.setAttribute('data-bs-theme', effective);
})();
</script>

@vite(['resources/js/theme.js', 'resources/sass/app.scss'])

{{-- Custom fonts --}}
<link href="https://fonts.googleapis.com/css?family=Roboto:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

{{-- Favicon --}}
<link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
<link rel="manifest" href="/images/favicon/site.webmanifest">
<link rel="mask-icon" href="/images/favicon/safari-pinned-tab.svg" color="#5bbad5">
<link rel="shortcut icon" href="/images/favicon/favicon.ico">
<meta name="msapplication-TileColor" content="#2b5797">
<meta name="msapplication-config" content="/images/favicon/browserconfig.xml">
<meta name="theme-color" content="#ffffff">
<meta name="robots" content="noindex">

{{-- Page specific header elements --}}
@yield('header')

@if(!empty(Config::get('app.tracking_script')))
    {{-- Tracking script of choice --}}
    {!! Config::get('app.tracking_script') !!}
@endif
