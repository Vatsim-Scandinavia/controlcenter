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
        </div>

        <div class="logo">
            <img src="{{ asset('images/logos/'.Config::get('app.logo')) }}">
            <a href="https://github.com/Vatsim-Scandinavia/controlcenter" target="_blank" class="version-front">Control Center v{{ config('app.version') }}</a>
        </div>
    </div>
@endsection