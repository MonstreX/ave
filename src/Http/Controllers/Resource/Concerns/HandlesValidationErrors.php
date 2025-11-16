<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Concerns;

use Monstrex\Ave\Support\CleanJsonResponse;

use Illuminate\Http\Request;
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

        if ($request->expectsJson() || $request->ajax()) {
            return CleanJsonResponse::make([
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
