@extends('layouts.auth')

@section('title', 'Login')

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
                      <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="user">
                      @csrf

                      <div class="form-group">
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-user @error('email') is-invalid @enderror" required placeholder="Email Address...">

                        @error('email')
                            <span class="invalid-feedback ml-2 mt-2" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                      </div>

                      <div class="form-group">
                        <input type="password" class="form-control form-control-user @error('password') is-invalid @enderror" name="password" required placeholder="Password">
                      
                        @error('password')
                            <span class="invalid-feedback ml-2 mt-2" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                      </div>

                      <div class="form-group">
                        <div class="custom-control custom-checkbox small">
                          <input type="checkbox" class="custom-control-input" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                          <label class="custom-control-label" for="remember">Remember Me</label>
                        </div>
                      </div>

                      <button type="submit" class="btn btn-primary btn-user btn-block">
                        Login
                      </button>
                    </form>

                    <hr>
                    @if (Route::has('password.request'))
                        <div class="text-center">
                          <a class="small" href="{{ route('password.request') }}">Forgot Password?</a>
                        </div>
                    @endif
                    <div class="text-center">
                        <a class="small" href="{{ route('register') }}">Create an Account!</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
  
        </div>
  
      </div>
@endsection