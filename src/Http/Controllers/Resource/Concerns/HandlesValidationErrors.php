<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait HandlesValidationErrors
{
    /**
     * Handle validation exception and return appropriate response
     *
     * @param ValidationException $exception
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|never
     */
    protected function handleValidationException(
        ValidationException $exception,
        Request $request
    ) {
        $errorMessages = $this->formatValidationErrors($exception->errors());

        Log::warning('Resource form validation failed', [
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
            'errors' => $exception->errors(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessages,
                'errors' => $exception->errors(),
            ], 422);
        }

        $request->session()->flash('toast', [
            'type' => 'danger',
            'message' => $errorMessages,
        ]);

        throw $exception;
    }

    /**
     * Format validation errors (must be defined in InteractsWithResources)
     *
     * @param array $errors
     * @return string
     */
    abstract protected function formatValidationErrors(array $errors): string;
}
