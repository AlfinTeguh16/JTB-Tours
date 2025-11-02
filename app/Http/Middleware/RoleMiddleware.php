<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage in routes: ->middleware('role:super_admin,admin')
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Silahkan login terlebih dahulu.');
        }

        // jika roles dikosongkan, izinkan
        if (empty($roles)) {
            return $next($request);
        }

        // jika user role cocok salah satu dari roles
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // else deny
        abort(403, 'Akses ditolak.');
    }
}
