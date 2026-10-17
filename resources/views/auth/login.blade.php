@extends('layouts.guest')

@section('title', 'Login - Invoice Manager')

@section('content')
<div class="row flex-grow">
    <div class="col-lg-4 mx-auto">
        <div class="auth-form-light text-left p-5">
            <div class="brand-logo">
                @include('partials.brand')
            </div>
            <h4>Hello! let's get started</h4>
            <h6 class="font-weight-light">Sign in to continue.</h6>
            <form class="pt-3" method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="form-group">
                    <div class="auth-field">
                        <i class="mdi mdi-email-outline auth-field-icon"></i>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control form-control-lg @error('email') is-invalid @enderror"
                            id="email"
                            placeholder="Email"
                            autocomplete="email"
                            autofocus
                        >
                    </div>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <div class="auth-field">
                        <i class="mdi mdi-lock-outline auth-field-icon"></i>
                        <input
                            type="password"
                            name="password"
                            class="form-control form-control-lg @error('password') is-invalid @enderror"
                            id="password"
                            placeholder="Password"
                            autocomplete="current-password"
                        >
                    </div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mt-3 d-grid gap-2">
                    <button type="submit" class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn">Sign in</button>
                </div>
                <div class="my-2 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <label class="form-check-label text-muted">
                            <input type="checkbox" name="remember" value="1" class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                            <i class="input-helper"></i>
                            Keep me signed in
                        </label>
                    </div>
                </div>
                <div class="text-center mt-4 font-weight-light">
                    Don't have an account? <a href="{{ route('register') }}" class="text-primary">Create</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
