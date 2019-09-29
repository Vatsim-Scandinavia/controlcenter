@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<div class="card o-hidden border-0 shadow-lg my-5">
    <div class="card-body p-0">
      <!-- Nested Row within Card Body -->
      <div class="row">
        <div class="col-lg-5 d-none d-lg-block bg-register-image"></div>
        <div class="col-lg-7">
          <div class="p-5">
            <div class="text-center">
              <h1 class="h4 text-gray-900 mb-4">Create an Account!</h1>
            </div>
            <form method="POST" action="{{ route('register') }}" class="user">
              @csrf

              <div class="form-group">
                  <input type="text" class="form-control form-control-user @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="Name">
              
                  @error('name')
                    <span class="invalid-feedback ml-2 mt-2" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                  @enderror
              </div>

              <div class="form-group">
                <input type="email" class="form-control form-control-user @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required placeholder="Email">
                
                @error('email')
                    <span class="invalid-feedback ml-2 mt-2" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                  @enderror
              </div>

              <div class="form-group row">
                <div class="col-sm-6 mb-3 mb-sm-0">
                  <input type="password" class="form-control form-control-user @error('password') is-invalid @enderror" name="password" required placeholder="Password">

                  @error('password')
                    <span class="invalid-feedback ml-2 mt-2" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                  @enderror
                </div>
                
                <div class="col-sm-6">
                  <input type="password" class="form-control form-control-user" name="password_confirmation" required placeholder="Password Confirmation">
                </div>
              </div>

              <button type="submit" class="btn btn-primary btn-user btn-block">
                Register Account
              </button>

            </form>
            <hr>
            <div class="text-center">
                <a class="small" href="{{ route('login') }}">Already have an account? Login!</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
