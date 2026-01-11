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
    var storedPreference = localStorage.getItem('user_theme_preference') || 
                          document.documentElement.getAttribute('data-user-theme') || 
                          'system';
    
    // Only apply non-system preferences to avoid FOUC
    // System preference detection is handled by theme.js after load
    if (storedPreference === 'light' || storedPreference === 'dark') {
        document.documentElement.setAttribute('data-theme', storedPreference);
    } else {
        // For 'system', detect but let theme.js handle the watching
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
    }
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