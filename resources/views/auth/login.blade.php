@extends('layouts.app')

@section('body_class', 'auth-page')
@section('shell_class', 'auth-shell')

@section('content')
    <div class="auth-layout">
        <section class="auth-hero">
            <div>
                <span class="auth-eyebrow">Secure Academic Data</span>
                <h1>Welcome Back</h1>
                <p class="muted">Sign in to manage student records, file uploads, search algorithms, sorting tools, and dashboard summaries.</p>

                <div class="auth-hero-grid">
                    <div class="auth-chip">Student CRUD</div>
                    <div class="auth-chip">CSV/XLSX Import</div>
                    <div class="auth-chip">Search & Sort</div>
                    <div class="auth-chip">File I/O Reports</div>
                </div>
            </div>

            <p class="muted" style="margin-bottom: 0;">Laravel dashboard with focused admin access.</p>
        </section>

        <section class="auth-panel">
            <div class="auth-panel-header">
                <x-simdex-logo variant="full" class="auth-logo" />
                <h1>Login</h1>
                <p class="muted">Enter your account details to continue.</p>
            </div>

            @if (session('success'))
                <div class="message success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="message error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="form-stack">
                    <div>
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Enter email address" required autofocus>
                    </div>

                    <div>
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" placeholder="Enter password" required>
                    </div>
                </div>

                <div class="remember-field">
                    <input id="remember" type="checkbox" name="remember" value="1">
                    <label for="remember">Remember me</label>
                </div>

                <button class="button full-width" type="submit" style="margin-top: 24px;">Login</button>
            </form>

            <div class="auth-links">
                <a class="button secondary" href="{{ route('register') }}">Create Account</a>
                <a class="button ghost" href="{{ route('password.request') }}">Reset Password</a>
            </div>
        </section>
    </div>
@endsection
