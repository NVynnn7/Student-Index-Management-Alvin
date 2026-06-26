<?php

namespace App\Http\Controllers;

use App\Mail\AccessCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    private const REGISTER_CHALLENGE_KEY = 'auth.register_challenge';

    private const REGISTER_ACCESS_KEY = 'auth.register_access';

    private const PASSWORD_RESET_CHALLENGE_KEY = 'auth.password_reset_challenge';

    private const PASSWORD_RESET_ACCESS_KEY = 'auth.password_reset_access';

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(Request $request): View
    {
        if ($request->boolean('restart')) {
            $request->session()->forget([
                self::REGISTER_CHALLENGE_KEY,
                self::REGISTER_ACCESS_KEY,
            ]);
        }

        $access = $this->confirmedAccess($request, self::REGISTER_ACCESS_KEY);
        $challenge = $access ? null : $this->activeChallenge($request, self::REGISTER_CHALLENGE_KEY);

        return view('auth.register', [
            'accessConfirmed' => $access !== null,
            'confirmedEmail' => $access['email'] ?? null,
            'challengePending' => $challenge !== null,
            'challengeEmail' => isset($challenge['email']) ? $this->maskEmail($challenge['email']) : null,
        ]);
    }

    public function showPasswordReset(Request $request): View
    {
        if ($request->boolean('restart')) {
            $request->session()->forget([
                self::PASSWORD_RESET_CHALLENGE_KEY,
                self::PASSWORD_RESET_ACCESS_KEY,
            ]);
        }

        $access = $this->confirmedAccess($request, self::PASSWORD_RESET_ACCESS_KEY);
        $challenge = $access ? null : $this->activeChallenge($request, self::PASSWORD_RESET_CHALLENGE_KEY);

        return view('auth.password-reset', [
            'accessConfirmed' => $access !== null,
            'confirmedEmail' => $access['email'] ?? null,
            'challengePending' => $challenge !== null,
            'challengeEmail' => isset($challenge['email']) ? $this->maskEmail($challenge['email']) : null,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('students.index'))
                ->with('success', 'Login successful. Welcome to the dashboard.')
                ->with('alert_type', 'login');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'The email or password is incorrect.']);
    }

    public function sendRegisterAccessCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'access_email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        return $this->sendAccessCode(
            $request,
            strtolower($data['access_email']),
            'registration',
            self::REGISTER_CHALLENGE_KEY,
        );
    }

    public function verifyRegisterAccessCode(Request $request): RedirectResponse
    {
        return $this->verifyAccessCode(
            $request,
            self::REGISTER_CHALLENGE_KEY,
            self::REGISTER_ACCESS_KEY,
            'register',
        );
    }

    public function register(Request $request): RedirectResponse
    {
        $access = $this->confirmedAccess($request, self::REGISTER_ACCESS_KEY);

        if (!$access) {
            return redirect()
                ->route('register')
                ->withErrors(['access_code' => 'Verify the email code before creating an account.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (User::where('email', $access['email'])->exists()) {
            $request->session()->forget(self::REGISTER_ACCESS_KEY);

            return redirect()
                ->route('register')
                ->withErrors(['access_email' => 'This email is already registered.']);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $access['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget([
            self::REGISTER_CHALLENGE_KEY,
            self::REGISTER_ACCESS_KEY,
        ]);

        return redirect()->route('students.index')
            ->with('success', 'Registration successful. Welcome to the dashboard.')
            ->with('alert_type', 'login');
    }

    public function sendPasswordResetAccessCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'access_email' => ['required', 'email', 'exists:users,email'],
        ]);
        $user = User::where('email', $data['access_email'])->firstOrFail();

        return $this->sendAccessCode(
            $request,
            $user->email,
            'password reset',
            self::PASSWORD_RESET_CHALLENGE_KEY,
            $user->id,
        );
    }

    public function verifyPasswordResetAccessCode(Request $request): RedirectResponse
    {
        return $this->verifyAccessCode(
            $request,
            self::PASSWORD_RESET_CHALLENGE_KEY,
            self::PASSWORD_RESET_ACCESS_KEY,
            'password.request',
        );
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $access = $this->confirmedAccess($request, self::PASSWORD_RESET_ACCESS_KEY);
        $user = $access && isset($access['user_id']) ? User::find($access['user_id']) : null;

        if (!$user || $user->email !== ($access['email'] ?? null)) {
            $request->session()->forget(self::PASSWORD_RESET_ACCESS_KEY);

            return redirect()
                ->route('password.request')
                ->withErrors(['access_code' => 'Verify the email code before setting a new password.']);
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);
        $request->session()->forget([
            self::PASSWORD_RESET_CHALLENGE_KEY,
            self::PASSWORD_RESET_ACCESS_KEY,
        ]);

        return redirect()->route('login')->with('success', 'Password updated. Please log in with your new password.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function sendAccessCode(
        Request $request,
        string $email,
        string $purpose,
        string $challengeKey,
        ?int $userId = null,
    ): RedirectResponse {
        $code = (string) random_int(100000, 999999);
        $expiresInMinutes = max(1, (int) ceil($this->codeTimeout() / 60));

        try {
            Mail::to($email)->send(new AccessCodeMail($code, $purpose, $expiresInMinutes));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput(['access_email' => $email])
                ->withErrors(['access_email' => 'The confirmation email could not be sent. Check the SMTP settings and try again.']);
        }

        $request->session()->put($challengeKey, [
            'email' => $email,
            'user_id' => $userId,
            'code_hash' => $this->hashCode($code),
            'expires_at' => now()->timestamp + $this->codeTimeout(),
            'attempts' => 0,
        ]);

        return back()->with('success', 'A 6-digit confirmation code was sent to your email.');
    }

    private function verifyAccessCode(
        Request $request,
        string $challengeKey,
        string $accessKey,
        string $redirectRoute,
    ): RedirectResponse {
        $data = $request->validate([
            'access_code' => ['required', 'digits:6'],
        ]);
        $challenge = $this->activeChallenge($request, $challengeKey);

        if (!$challenge) {
            return redirect()
                ->route($redirectRoute)
                ->withErrors(['access_code' => 'The code has expired. Request a new confirmation code.']);
        }

        if ((int) ($challenge['attempts'] ?? 0) >= $this->maxCodeAttempts()) {
            $request->session()->forget($challengeKey);

            return redirect()
                ->route($redirectRoute)
                ->withErrors(['access_code' => 'Too many incorrect attempts. Request a new confirmation code.']);
        }

        if (!hash_equals($challenge['code_hash'], $this->hashCode($data['access_code']))) {
            $challenge['attempts'] = (int) ($challenge['attempts'] ?? 0) + 1;
            $request->session()->put($challengeKey, $challenge);

            return back()->withErrors(['access_code' => 'The confirmation code is incorrect.']);
        }

        $request->session()->forget($challengeKey);
        $request->session()->put($accessKey, [
            'email' => $challenge['email'],
            'user_id' => $challenge['user_id'] ?? null,
            'confirmed_at' => now()->timestamp,
        ]);

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Email confirmed. You can continue to the next step.');
    }

    private function activeChallenge(Request $request, string $sessionKey): ?array
    {
        $challenge = $request->session()->get($sessionKey);

        if (
            !is_array($challenge)
            || empty($challenge['email'])
            || empty($challenge['code_hash'])
            || empty($challenge['expires_at'])
            || now()->timestamp > (int) $challenge['expires_at']
        ) {
            $request->session()->forget($sessionKey);

            return null;
        }

        return $challenge;
    }

    private function confirmedAccess(Request $request, string $sessionKey): ?array
    {
        $confirmation = $request->session()->get($sessionKey);

        if (
            !is_array($confirmation)
            || empty($confirmation['email'])
            || empty($confirmation['confirmed_at'])
            || now()->timestamp - (int) $confirmation['confirmed_at'] > $this->accessTimeout()
        ) {
            $request->session()->forget($sessionKey);

            return null;
        }

        return $confirmation;
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function codeTimeout(): int
    {
        return (int) config('auth.access_code_timeout', 600);
    }

    private function accessTimeout(): int
    {
        return (int) config('auth.access_confirmation_timeout', 600);
    }

    private function maxCodeAttempts(): int
    {
        return (int) config('auth.access_code_max_attempts', 5);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.str_repeat('•', max(3, mb_strlen($local) - mb_strlen($visible))).'@'.$domain;
    }
}
