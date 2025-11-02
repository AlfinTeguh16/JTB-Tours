<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    protected $maxAttempts = 5;
    protected $decaySeconds = 60 * 5; // 5 minutes

    // show login form
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // handle login
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
        ]);

        $email = (string) $request->input('email');
        $throttleKey = $this->throttleKey($email, $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors(['email' => "Terlalu banyak percobaan login. Coba lagi setelah {$seconds} detik."]);
        }

        $credentials = $request->only('email','password');
        $remember = $request->boolean('remember');

        try {
            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();
                RateLimiter::clear($throttleKey);

                // set user online (optional)
                $user = Auth::user();
                if ($user) {
                    // if you want to set online status on login, uncomment:
                    $user->status = 'online';
                    $user->saveQuietly(); // avoid firing events if unwanted
                }

                return $this->redirectToRole($user);
            }

            // failed attempt
            RateLimiter::hit($throttleKey, $this->decaySeconds);

            $remaining = $this->maxAttempts - RateLimiter::attempts($throttleKey);
            $message = 'Email atau password salah.';
            if ($remaining > 0) $message .= " Sisa percobaan: {$remaining}.";

            return back()->withErrors(['email' => $message])->onlyInput('email');
        } catch (\Throwable $e) {
            Log::error('AuthController.login error: '.$e->getMessage(), ['email'=>$email,'trace'=>$e->getTraceAsString()]);
            return back()->withErrors(['email' => 'Terjadi kesalahan saat proses login. Silakan coba lagi.'])->onlyInput('email');
        }
    }

    // show registration (optional)
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    // handle registration (optional, disable in prod if not wanted)
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'phone' => ['nullable','string','max:25'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'], // rely on model mutator to hash
                'role' => 'staff', // default role, adjust as needed
                'join_date' => now()->toDateString(),
                'monthly_work_limit' => 200,
                'used_hours' => 0,
                'status' => 'offline',
            ]);

            Auth::login($user);
            DB::commit();

            // set online if desired
            $user->status = 'online';
            $user->saveQuietly();

            return $this->redirectToRole($user);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AuthController.register error: '.$e->getMessage(), ['payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return back()->withInput()->withErrors(['email' => 'Gagal membuat akun, coba lagi.']);
        }
    }

    // logout
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user) {
                // set offline on logout (recommended for driver/guide)
                $user->status = 'offline';
                $user->saveQuietly();
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success','Kamu berhasil logout.');
        } catch (\Throwable $e) {
            Log::error('AuthController.logout error: '.$e->getMessage(), ['user_id'=>optional(Auth::user())->id, 'trace'=>$e->getTraceAsString()]);
            // Even on error, force logout client-side
            try {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } catch (\Throwable $ex) {
                // ignore
            }
            return redirect()->route('login')->with('error','Logout gagal sepenuhnya tapi sesi sudah dibersihkan.');
        }
    }

    // helper: redirect based on role
    protected function redirectToRole(User $user)
    {
        // customize per role routes
        if (!$user) {
            return redirect()->intended(route('dashboard'));
        }

        return match($user->role) {
            'super_admin' => redirect()->intended(route('dashboard'))->getTargetUrl(),
            'admin' => redirect()->intended(route('dashboard'))->getTargetUrl(),
            'staff' => redirect()->intended(route('dashboard'))->getTargetUrl(),
            'driver', 'guide' => redirect()->intended(route('assignments.my'))->getTargetUrl(),
            default => redirect()->intended(route('dashboard'))->getTargetUrl(),
        };
    }

    /**
     * Helper to build throttle key for a user+IP
     */
    protected function throttleKey(string $email, ?string $ip = null): string
    {
        $ipPart = $ip ? (string) $ip : request()->ip();
        return Str::lower($email).'|'.$ipPart;
    }
}
