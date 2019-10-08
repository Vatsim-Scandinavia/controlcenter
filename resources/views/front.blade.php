@extends('layouts.app')

@section('title', 'Login')

@section('content-master')
    <div class="front-cover">
        <div class="content">
            <div class="content-title"><i class="fas fa-sleigh"></i> {{ config('app.name') }}</div>
            <div class="content-description">Scandinavian Training Administration</div>
            <a href="{{ route('login') }}" class="btn btn-success">Login</a>
        </div>
    </div>
@endsection