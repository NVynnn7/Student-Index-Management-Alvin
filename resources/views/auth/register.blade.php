@extends('layouts.app')

@section('body_class', 'auth-page')
@section('shell_class', 'auth-shell')

@section('content')
    @php
        $currentStep = $accessConfirmed ? 3 : ($challengePending ? 2 : 1);
    @endphp

    <div class="auth-layout">
        <section class="auth-hero">
            <div>
                <span class="auth-eyebrow">Verified Registration</span>
                <h1>Create Your Account</h1>
                <p class="muted">Verify the new account email through a secure SMTP code before entering the account details.</p>

                <div class="auth-hero-grid">
                    <div class="auth-chip">Email Code</div>
                    <div class="auth-chip">10-Minute Expiry</div>
                    <div class="auth-chip">Double Password Entry</div>
                    <div class="auth-chip">Protected Account</div>
                </div>
            </div>

            <p class="muted" style="margin-bottom: 0;">The registered email is locked to the address that successfully receives and verifies the code.</p>
        </section>

        <section class="auth-panel">
            <div class="auth-panel-header">
                <x-simdex-logo variant="full" class="auth-logo" />
                <h1>{{ $currentStep === 1 ? 'Verify Email' : ($currentStep === 2 ? 'Enter Code' : 'Register') }}</h1>
                <p class="muted">
                    {{ $currentStep === 1
                        ? 'Send a confirmation code to the new account email.'
                        : ($currentStep === 2
                            ? 'Enter the 6-digit code delivered through SMTP.'
                            : 'Complete the account using the verified email.') }}
                </p>
            </div>

            <div class="auth-stepper three" aria-label="Registration progress">
                <span class="{{ $currentStep === 1 ? 'active' : 'complete' }}"><b>1</b> Email</span>
                <span class="{{ $currentStep === 2 ? 'active' : ($currentStep > 2 ? 'complete' : '') }}"><b>2</b> Code</span>
                <span class="{{ $currentStep === 3 ? 'active' : '' }}"><b>3</b> Account</span>
            </div>

            @if (session('success'))
                <div class="message success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="message error">{{ $errors->first() }}</div>
            @endif

            @if ($currentStep === 1)
                <div class="auth-access-note">
                    <strong>SMTP email confirmation required</strong>
                    <span>The email must not already be registered. A 6-digit code will be sent before the registration form opens.</span>
                </div>

                <form method="POST" action="{{ route('register.access.send') }}">
                    @csrf

                    <div class="form-stack">
                        <div>
                            <label for="access_email">New Account Email</label>
                            <input id="access_email" type="email" name="access_email" value="{{ old('access_email') }}" placeholder="Enter the email to register" autocomplete="email" required autofocus>
                        </div>
                    </div>

                    <button class="button full-width auth-submit" type="submit">Send Confirmation Code</button>
                </form>
            @elseif ($currentStep === 2)
                <div class="auth-access-note">
                    <strong>Check {{ $challengeEmail }}</strong>
                    <span>Enter the 6-digit code. It expires in 10 minutes and is limited to five incorrect attempts.</span>
                </div>

                <form method="POST" action="{{ route('register.access.verify') }}">
                    @csrf

                    <div class="form-stack">
                        <div>
                            <label for="access_code">Confirmation Code</label>
                            <input class="auth-code-input" id="access_code" name="access_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" autocomplete="one-time-code" required autofocus>
                        </div>
                    </div>

                    <button class="button full-width auth-submit" type="submit">Verify Code</button>
                </form>

                <div class="auth-inline-actions">
                    <a href="{{ route('register', ['restart' => 1]) }}">Use another email</a>
                    <span>Request a new code by returning to the email step.</span>
                </div>
            @else
                <div class="auth-verified">
                    <div>
                        <strong>Email confirmed</strong>
                        <span>{{ $confirmedEmail }}</span>
                    </div>
                    <a href="{{ route('register', ['restart' => 1]) }}">Change</a>
                </div>

                <form method="POST" action="{{ route('register.store') }}">
                    @csrf

                    <div class="form-stack">
                        <div>
                            <label for="name">Name</label>
                            <input id="name" name="name" value="{{ old('name') }}" placeholder="Enter full name" required autofocus>
                        </div>

                        <div>
                            <label for="password">Password</label>
                            <input id="password" type="password" name="password" placeholder="Minimum 8 characters" autocomplete="new-password" required>
                        </div>

                        <div>
                            <label for="password_confirmation">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Enter the same password again" autocomplete="new-password" required>
                        </div>
                    </div>

                    <button class="button full-width auth-submit" type="submit">Create Account</button>
                </form>
            @endif

            <div class="auth-links">
                <a class="button secondary" href="{{ route('login') }}">Back to Login</a>
                <a class="button ghost" href="{{ route('password.request') }}">Reset Password</a>
            </div>
        </section>
    </div>
@endsection
