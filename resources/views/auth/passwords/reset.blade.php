@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="row justify-content-center">

    <div class="col-xl-10 col-lg-12 col-md-9">

        <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-0">
            <!-- Nested Row within Card Body -->
            <div class="row">
            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>

            <div class="col-lg-6">
                <div class="p-5">
                <div class="text-center">
                    <h1 class="h4 text-gray-900 mb-4">Reset Password</h1>
                </div>

                <form method="POST" action="{{ route('password.update') }}" class="user">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="form-group">
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-user @error('email') is-invalid @enderror" required placeholder="Email Address...">

                        @error('email')
                            <span class="invalid-feedback ml-2 mt-2" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <input type="password" name="password" class="form-control form-control-user @error('password') is-invalid @enderror" required placeholder="Password">

                        @error('password')
                            <span class="invalid-feedback ml-2 mt-2" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <input type="password" name="password_confirmation" class="form-control form-control-user" required placeholder="Password Confirmation">
                    </div>

                    <button type="submit" class="btn btn-primary btn-user btn-block">
                        Reset Password
                    </button>
                </form>
                </div>
            </div>
            </div>
        </div>
        </div>

    </div>

    </div>
@endsection
