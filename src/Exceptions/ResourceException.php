<?php

namespace Monstrex\Ave\Exceptions;

class ResourceException extends AveException
{
    public function __construct(string $message = "", int $code = 500)
    {
        parent::__construct($message, $code);
        $this->statusCode = $code;
    }

    public static function notFound(string $slug): self
    {
        return new self("Resource '{$slug}' not found", 404);
    }

    public static function modelNotFound(string $slug, mixed $id): self
    {
        return new self("Model '{$id}' not found for resource '{$slug}'", 404);
    }

    public static function unauthorized(string $slug, string $ability): self
    {
        return new self("Unauthorized to '{$ability}' on resource '{$slug}'", 403);
    }

    public static function bulkActionUnauthorized(
        string $slug,
        string $ability,
        int $totalCount,
        int $unauthorizedCount,
        ?array $unauthorizedIds = null
    ): self
    {
        $message = sprintf(
            "Bulk action '%s' denied for %d out of %d record(s) in resource '%s'.",
            $ability,
            $unauthorizedCount,
            $totalCount,
            $slug
        );

        // In debug mode, show which IDs are unauthorized
        // Use try-catch to handle cases where config is not available (e.g., unit tests)
        $isDebug = false;
        try {
            $isDebug = config('app.debug', false);
        } catch (\Throwable $e) {
            // Config not available, skip debug info
        }

        if ($unauthorizedIds !== null && $isDebug) {
            $idsPreview = count($unauthorizedIds) > 10
                ? implode(', ', array_slice($unauthorizedIds, 0, 10)) . '...'
                : implode(', ', $unauthorizedIds);

            $message .= " Unauthorized IDs: {$idsPreview}";
        }

        return new self($message, 403);
    }

    public static function invalidModel(string $resourceClass): self
    {
        return new self("Resource '{$resourceClass}' has no valid model class", 500);
    }
}
