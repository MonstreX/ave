<?php

namespace Monstrex\Ave\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Handle exceptions for Ave admin routes
 *
 * Catches exceptions in Ave routes and renders them as beautiful error pages
 * instead of standard Laravel error responses.
 */
class HandleAveExceptions
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            return $this->render($e, $request);
        }
    }

    /**
     * Render the exception
     */
    private function render(Throwable $e, Request $request): Response
    {
        if (! $this->matchesAveRoute($request)) {
            throw $e;
        }

        // Get status code
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        // Only handle specific error codes
        if (!in_array($statusCode, [403, 404, 422, 500])) {
            throw $e; // Let Laravel handle it
        }

        // Default messages
        $defaultMessages = [
            403 => 'You don\'t have permission to access this resource.',
            404 => 'The page you\'re looking for doesn\'t exist.',
            422 => 'Invalid configuration or request.',
            500 => 'Something went wrong on our end.',
        ];

        $message = $e->getMessage() ?: ($defaultMessages[$statusCode] ?? 'An error occurred.');

        // Handle AJAX/API requests - return JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'code' => $statusCode,
            ], $statusCode);
        }

        // Handle regular requests - return HTML error view
        return response()->view('ave::errors.' . $statusCode, [
            'code' => $statusCode,
            'message' => $message,
            'exception' => config('app.debug') ? $e : null,
        ], $statusCode);
    }

    /**
     * Determine if the current request targets Ave admin routes.
     */
    private function matchesAveRoute(Request $request): bool
    {
        foreach ($this->routePatterns() as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build glob patterns for Ave route prefix.
     *
     * @return array<int,string>
     */
    private function routePatterns(): array
    {
        $prefix = trim((string) config('ave.route_prefix', 'admin'), '/');

        if ($prefix === '') {
            return ['admin', 'admin/*'];
        }

        return [$prefix, "{$prefix}/*"];
    }
}
