<?php

namespace Monstrex\Ave\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get locale from authenticated user
        if (Auth::check()) {
            // Refresh user from database to get latest locale
            $user = Auth::user();
            if ($user && method_exists($user, 'refresh')) {
                $user->refresh();
            }
            $locale = $user->locale ?? config('app.locale', 'en');
        } else {
            $locale = config('app.locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
