
<!DOCTYPE html>
@php
    $themePreference = Auth::check() ? (Auth::user()->theme_preference ?? 'system') : 'system';
    // For explicit preferences, set theme server-side. For 'system', let JavaScript handle it.
    $initialTheme = ($themePreference === 'dark' || $themePreference === 'light') ? $themePreference : 'light';
@endphp
<html lang="en" data-theme-preference="{{ $themePreference }}" data-theme="{{ $initialTheme }}">
    <head>
        @include('layouts.header')
        <script>
            // Apply theme immediately before page renders to prevent FOUC
            (function() {
                const html = document.documentElement;
                const preference = html.getAttribute('data-theme-preference') || 'system';
                let theme = 'light';
                
                if (preference === 'dark') {
                    theme = 'dark';
                } else if (preference === 'system') {
                    // Check system preference
                    const systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    theme = systemPrefersDark ? 'dark' : 'light';
                }
                
                // Apply theme immediately
                html.setAttribute('data-theme', theme);
            })();
        </script>
    </head>

    <body>

    {{-- Page Wrapper --}}
    <div id="wrapper">

        @auth
            @include('layouts.sidebar')
        @endauth

        {{-- Content Wrapper --}}
        <div id="content-wrapper" class="d-flex flex-column">

        {{-- Main Content --}}
        <div id="content">

            @auth
                @include('layouts.topbar')
            @endauth

            @yield('content-master') {{-- For special things to be done outside the container --}}
            
            <div class="container-fluid">

                @if(!Route::is('front'))

                    <div class="page-title d-flex justify-content-between">
                        <h3 class="text-gray-800">
                            @yield('title', 'Page Title')
                        </h3>
                        @yield('title-flex')
                    </div>

                    @if(Session::has('success') OR isset($success))
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-lg fa-check-circle"></i>&nbsp;{!! Session::pull("success") !!}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @if(count($errors) > 1)
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @else
                                {{ $errors->first() }}
                            @endif
                        </div>
                    @endif
                @endif

                @yield('content')
            </div>

        </div>
        {{-- End of Main Content --}}

        </div>
        {{-- End of Content Wrapper --}}

    </div>
    {{-- End of Page Wrapper --}}

    {{-- JavaScripts--}}
    @vite(['resources/js/app.js'])
    @include('scripts.global')
    
    @yield('js')
    
    </body>
</html>
