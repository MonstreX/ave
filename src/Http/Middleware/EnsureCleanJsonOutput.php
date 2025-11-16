<?php

namespace Monstrex\Ave\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCleanJsonOutput
{
    /**
     * Handle an incoming request.
     * Suppresses PHP warnings/notices for JSON responses.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set custom error handler to suppress warnings during request
        $previousHandler = set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$previousHandler) {
            // Only suppress warnings and notices, not errors
            if ($errno === E_WARNING || $errno === E_NOTICE || $errno === E_USER_WARNING || $errno === E_USER_NOTICE) {
                // Log to Laravel log instead of output
                if (str_contains($errfile, 'sebastian') || str_contains($errfile, 'phpunit')) {
                    // Silently ignore PHPUnit/Sebastian warnings
                    return true;
                }
                
                // Log other warnings
                \Log::warning("PHP Warning suppressed: $errstr in $errfile:$errline");
                return true;
            }
            
            // Let other errors pass through to previous handler
            if ($previousHandler) {
                return call_user_func($previousHandler, $errno, $errstr, $errfile, $errline);
            }
            
            return false;
        });

        $response = $next($request);

        // Restore previous error handler
        restore_error_handler();

        return $response;
    }
}
