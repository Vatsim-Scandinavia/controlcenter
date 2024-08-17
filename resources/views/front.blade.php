@extends('layouts.app')

@section('title', 'Login')

@section('content-master')
    <div class="front-cover">
        <div class="content">
            
            @if(Session::has('error') OR isset($error))
            <div class="alert alert-danger" role="alert">
                <i class="fa fa-lg fa-exclamation-circle"></i> {!! Session::has('error') ? Session::pull("error") : $error !!}
            </div>
            @endif
            
            @if(Session::has('success') OR isset($success))
            <div class="alert alert-success" role="alert">
                {!! Session::has('success') ? Session::pull("success") : $error !!}
            </div>
            @endif
            
            <div class="content-title"><img src="{{ asset('images/control-tower.svg') }}"> {{ config('app.name') }}</div>
            <div class="content-description">
                @if(config('app.owner_code') == 'SCA')
                Scandinavian Training Administration
                @else
                Training Administration
                @endif
            </div>
            <a href="{{ route('login') }}" class="btn btn-success">Login</a>
            
            @env('local')
                <div class="accordion content-wrapper" id="devloginAccordion">
                    <div class="accordion-item m-auto mt-5">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#devlogin" aria-expanded="false" aria-controls="devlogin">
                                <i class="fa fa-lg fa-key"></i>&nbsp;
                                Login as user
                            </button>
                        </h2>
                        <div id="devlogin" class="accordion-collapse collapse" data-bs-parent="#devloginAccordion">
                            <div class="accordion-body">
                                <div class="alert alert-warning" role="alert">
                                    This function is only available in local environment.
                                </div>
                                <div class="d-flex flex-row flex-wrap gap-2">
                                    <x-login-link key="10000001" label="10000001" />
                                    <x-login-link key="10000002" label="10000002" />
                                    <x-login-link key="10000003" label="10000003" />
                                    <x-login-link key="10000004" label="10000004" />
                                    <x-login-link key="10000005" label="10000005" />
                                    <x-login-link key="10000006" label="10000006" />
                                    <x-login-link key="10000007" label="10000007" />
                                    <x-login-link key="10000008" label="10000008" />
                                    <x-login-link key="10000009" label="10000009" />
                                    <x-login-link key="10000010" label="10000010" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endenv
        
        </div>
       
        
        <div class="logo">
            <img src="{{ asset('images/logos/'.Config::get('app.logo')) }}">
            <a href="https://github.com/Vatsim-Scandinavia/controlcenter" target="_blank" class="version-front">Control Center v{{ config('app.version') }}</a>
        </div>
    </div>
@endsection