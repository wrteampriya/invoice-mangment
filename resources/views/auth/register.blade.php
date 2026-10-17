@extends('layouts.guest')

@section('title', 'Register - Invoice Manager')

@section('content')
<div class="row flex-grow">
    <div class="col-lg-4 mx-auto">
        <div class="auth-form-light text-left p-5">
            <div class="brand-logo">
                @include('partials.brand')
            </div>
            <h4>New here?</h4>
            <h6 class="font-weight-light">Signing up is easy. It only takes a few steps</h6>
            <form class="pt-3" method="POST" action="{{ route('register.post') }}">
                @csrf
                <div class="form-group">
                    <div class="auth-field">
                        <i class="mdi mdi-account-outline auth-field-icon"></i>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="form-control form-control-lg @error('name') is-invalid @enderror"
                            id="name"
                            placeholder="Name"
                            autocomplete="name"
                            autofocus
                        >
                    </div>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
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
                            autocomplete="new-password"
                        >
                    </div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <div class="auth-field">
                        <i class="mdi mdi-lock-check-outline auth-field-icon"></i>
                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-control form-control-lg"
                            id="password_confirmation"
                            placeholder="Confirm Password"
                            autocomplete="new-password"
                        >
                    </div>
                </div>
                <div class="mt-3 d-grid gap-2">
                    <button type="submit" class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn">Sign up</button>
                </div>
                <div class="text-center mt-4 font-weight-light">
                    Already have an account? <a href="{{ route('login') }}" class="text-primary">Login</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
