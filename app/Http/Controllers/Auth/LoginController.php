<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LoginController extends Controller
{
    /**
     * Failed attempts allowed per email address and IP before login is paused for a minute.
     */
    private const MAX_ATTEMPTS = 5;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        // A new session id after login prevents session fixation.
        $request->session()->regenerate();

        // Back to the page asked for before signing in, if this user's role may open it.
        // Otherwise their start page: the dashboard, or the products list for roles
        // without a dashboard (a Seller who first opened "/" would else get a 403).
        $user = $request->user();
        $intended = $request->session()->pull('url.intended');

        return redirect()->to(is_string($intended) && $this->mayOpen($user, $intended) ? $intended : $user->homeUrl());
    }

    /**
     * Whether the user passes the role gates of the route behind this URL, e.g.
     * "can:access-customers". Only decides where to send the user after login:
     * the page itself still checks access. Unknown URLs, and gates that take
     * arguments (none in this project), count as not allowed.
     */
    private function mayOpen(User $user, string $url): bool
    {
        try {
            $route = app('router')->getRoutes()->match(Request::create($url));
        } catch (Throwable) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'can:') && ! $user->can(Str::after($middleware, 'can:'))) {
                return false;
            }
        }

        return true;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
