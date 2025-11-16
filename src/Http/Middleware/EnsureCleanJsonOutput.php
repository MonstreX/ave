<?php

namespace Monstrex\Ave\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCleanJsonOutput
{
    /**
     * Handle an incoming request.
     * Cleans PHP warnings/notices from JSON responses caused by dependencies.
     */
    public function handle(Request $request, Closure $next): Response
    {
        ob_start();

        $response = $next($request);

        $contentType = $response->headers->get('Content-Type', '');

        if (str_contains($contentType, 'application/json')) {
            $output = ob_get_clean();

            // If there's garbage before JSON (PHP warnings/notices) - cut it out
            if (!empty($output)) {
                $jsonStart = strpos($output, '{');

                if ($jsonStart !== false && $jsonStart > 0) {
                    $cleanJson = substr($output, $jsonStart);
                    $response->setContent($cleanJson);
                } elseif ($jsonStart === false) {
                    // No JSON found, keep output as is (might be error)
                    $response->setContent($output);
                }
            }
        } else {
            ob_end_flush();
        }

        return $response;
    }
}
