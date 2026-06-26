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
                <span class="auth-eyebrow">Secure Account Recovery</span>
                <h1>Reset Password</h1>
                <p class="muted">A secure code is sent to the registered email before a new password can be created.</p>

                <div class="auth-hero-grid">
                    <div class="auth-chip">Account Lookup</div>
                    <div class="auth-chip">SMTP Code</div>
                    <div class="auth-chip">New Password</div>
                    <div class="auth-chip">Double Confirmation</div>
                </div>
            </div>

            <p class="muted" style="margin-bottom: 0;">Only the account connected to the verified email can have its password changed.</p>
        </section>

        <section class="auth-panel">
            <div class="auth-panel-header">
                <x-simdex-logo variant="full" class="auth-logo" />
                <h1>{{ $currentStep === 1 ? 'Find Account' : ($currentStep === 2 ? 'Enter Code' : 'New Password') }}</h1>
                <p class="muted">
                    {{ $currentStep === 1
                        ? 'Send a confirmation code to the registered email.'
                        : ($currentStep === 2
                            ? 'Enter the 6-digit code delivered through SMTP.'
                            : 'Set and confirm the new password.') }}
                </p>
            </div>

            <div class="auth-stepper three" aria-label="Password reset progress">
                <span class="{{ $currentStep === 1 ? 'active' : 'complete' }}"><b>1</b> Email</span>
                <span class="{{ $currentStep === 2 ? 'active' : ($currentStep > 2 ? 'complete' : '') }}"><b>2</b> Code</span>
                <span class="{{ $currentStep === 3 ? 'active' : '' }}"><b>3</b> Password</span>
            </div>

            @if (session('success'))
                <div class="message success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="message error">{{ $errors->first() }}</div>
            @endif

            @if ($currentStep === 1)
                <div class="auth-access-note">
                    <strong>Email code required</strong>
                    <span>Enter the email already registered to the account. The password form stays locked until the code is verified.</span>
                </div>

                <form method="POST" action="{{ route('password.access.send') }}">
                    @csrf

                    <div class="form-stack">
                        <div>
                            <label for="access_email">Registered Email</label>
                            <input id="access_email" type="email" name="access_email" value="{{ old('access_email') }}" placeholder="Enter registered email" autocomplete="email" required autofocus>
                        </div>
                    </div>

                    <button class="button full-width auth-submit" type="submit">Send Confirmation Code</button>
                </form>
            @elseif ($currentStep === 2)
                <div class="auth-access-note">
                    <strong>Check {{ $challengeEmail }}</strong>
                    <span>Enter the 6-digit code. It expires in 10 minutes and is limited to five incorrect attempts.</span>
                </div>

                <form method="POST" action="{{ route('password.access.verify') }}">
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
                    <a href="{{ route('password.request', ['restart' => 1]) }}">Use another email</a>
                    <span>Request a new code by returning to the email step.</span>
                </div>
            @else
                <div class="auth-verified">
                    <div>
                        <strong>Email confirmed</strong>
                        <span>{{ $confirmedEmail }}</span>
                    </div>
                    <a href="{{ route('password.request', ['restart' => 1]) }}">Change</a>
                </div>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <div class="form-stack">
                        <div>
                            <label for="password">New Password</label>
                            <input id="password" type="password" name="password" placeholder="Minimum 8 characters" autocomplete="new-password" required autofocus>
                        </div>

                        <div>
                            <label for="password_confirmation">Confirm New Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Enter the same password again" autocomplete="new-password" required>
                        </div>
                    </div>

                    <button class="button full-width auth-submit" type="submit">Update Password</button>
                </form>
            @endif

            <div class="auth-links">
                <a class="button secondary" href="{{ route('login') }}">Back to Login</a>
                <a class="button ghost" href="{{ route('register') }}">Create Account</a>
            </div>
        </section>
    </div>
@endsection
