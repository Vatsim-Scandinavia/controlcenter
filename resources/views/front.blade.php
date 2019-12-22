@extends('layouts.app')

@section('title', 'Login')

@section('content-master')
    <div class="front-cover">
        <div class="content">

            
            @if($errors->any())
            <div class="alert alert-danger" role="alert">
                Server Error: "{{ $errors->first() }}". Please contact Web Department if issue presists.
            </div>
            @endif
            
            <div class="content-title"><i class="fas fa-sleigh"></i> {{ config('app.name') }}</div>
            <div class="content-description">Scandinavian Training Administration</div>
            <a href="{{ route('login') }}" class="btn btn-success">Login</a>
        </div>

        <div class="logo">
            <img src="images/vatsca-logo-negative.svg">
        </div>
    </div>
@endsection